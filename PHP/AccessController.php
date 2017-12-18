<?php

namespace Auth\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Shared\ApiBundle\Controller\AbstractController;
use Shared\TokenBundle\Service\UserTokenService;
use Shared\AuthBundle\Service\User\Provider\User\ProviderService as UserProviderService;
use Shared\TokenBundle\Entity\UserToken;

use Auth\ApiBundle\Dto\Controller\Access\Request\RefreshDto as RefreshRequest;
use Auth\ApiBundle\Dto\Controller\Access\Response\RefreshDto as RefreshResponse;
use Auth\ApiBundle\Dto\Controller\Access\Request\RevokeDto as RevokeRequest;
use Auth\ApiBundle\Dto\Controller\Access\Response\RevokeDto as RevokeResponse;

/**
 * Class AccessController
 * @package Auth\ApiBundle\Controller
 *
 * @DI\Service("auth.api.controller.access")
 * @Route("/access", service="auth.api.controller.access")
 */
class AccessController extends AbstractController
{
    /** @var UserTokenService */
    protected $userTokenService;

    /** @var UserProviderService */
    protected $userProviderService;

    /**
     * @param UserTokenService $userTokenService
     * @param UserProviderService $userProviderService
     *
     * @DI\InjectParams({
     *     "userTokenService" = @DI\Inject("shared.token.service.token.user"),
     *     "userProviderService" = @DI\Inject("shared.auth.service.provider.user")
     * })
     */
    public function __construct(UserTokenService $userTokenService, UserProviderService $userProviderService)
    {
        $this->userTokenService = $userTokenService;
        $this->userProviderService = $userProviderService;
    }

    /**
     * Обновляет/продлевает токен
     *
     * <pre>
     * Для обновления необходимо предоставить текущий валидный токен.
     * Токен считается валидным, если:
     *  - подписан сервисом авторизации
     *  - его срок действия истек не более недели назад
     *  - ранее не обновлялся
     *
     * После успешного выполнения запроса старый токен будет отмечен как невалидный.
     * Взамен старого токена будет выдан новый токен с обновленным временем действия.
     * Повторное обновление одного и того же токена невозможно.
     * </pre>
     *
     * <strong>Токен</strong>
     * <pre>
     * Токен представляет из себя JWT подписанный по алгоритму RS256.
     * Для проверки подписи клиент должен знать public key.
     * В payload'e JWT передается следующий набор параметров:
     *
     * {
     *     "iss": "jivosite",
     *     "jti": "708510192263338393657d96029b324c0.69717862",
     *     "aud": [
     *         "jivoapp",
     *         "chatserver",
     *         "partnerapp",
     *         "servicezone"
     *     ],
     *     "iat": 1473762000,
     *     "exp": 1473848400,
     *     "user_id": 1,
     *     "user_role": "super_user",
     *     "scope_list": []
     * }
     *
     * Подробно о каждом параметре:
     * iss - идентификатор сервиса выдавшего токен. Всегда равен jivosite. Приложение должно отклонять токены с другим
     * значением данного параметра.
     *
     * jti - уникальный идентифкатор токена.
     *
     * aud - список идентификаторов сервисов, для которых токен предназначен. Возможные значения для aud:
     *     partnerapp - приложение партнера
     *     jivoapp - приложение оператора
     *     chatserver - чат сервер
     *     partnerapp - приложение партнера
     *     servicezone - сервис зона
     *
     * Приложение, проверяющее токен, должно найти себя в списке aud. Если приложение не нашло себя в этом списке, токен
     * должен быть отклонен.
     *
     * iat - время выдачи токена в unix timestamp формате
     * exp - время до которого токен действует в unix timestamp формате. Приложение должно отклонять токены с истекшим
     * временем дейсвтия.
     *
     * user_id - идентификатор пользователя
     *
     * user_role - роль пользователя. Возможноые значения:
     *     agent - роль агента
     *     agent_su - роль агента с правами администратора
     *     partner - роль партнера
     *     super_user - роль супер пользователя
     * scope_list - список прав токена
     * </pre>
     *
     * <strong>Возможные варианты ошибок</strong>
     * <pre>
     * invalid_token - передан невалидный токен
     * </pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Токен",
     *  output={
     *      "class"="Auth\ApiBundle\Dto\Controller\Access\Response\RefreshDto",
     *      "parsers"={"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *  },
     *  statusCodes={
     *      200="Успешное выполнение запроса",
     *      400="Невалидный запрос"
     *  }
     * )
     *
     * @param RefreshRequest $requestDto
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Method("POST")
     * @Route("/refresh")
     *
     * @ParamConverter("requestDto", class="Auth\ApiBundle\Dto\Controller\Access\Request\RefreshDto")
     */
    public function refreshAction(RefreshRequest $requestDto)
    {
        $refreshResultDto = $this->userTokenService->refreshTokenByHash($requestDto->getToken());

        $response = new RefreshResponse();

        /** @var UserToken $token */
        $token = $refreshResultDto->getAccessToken();

        if ($token) {
            $response->setAccessToken($token->getHash());

            $provider = $this->userProviderService->getUserProviderByToken($token);

            $user = $provider->getUserByToken($token);
            if ($user) {
                $response->setEndpointList($provider->getEndpointList($user));
            }
        }
        $response->setOk(!$refreshResultDto->getErrorList());
        $response->setErrorList($refreshResultDto->getErrorList());
        $response->setWarningList($refreshResultDto->getWarningList());

        return $this->buildResponse($response);
    }

    /**
     * Инвалидирует все легитимно выданные авторизационные токены связанного пользователя
     *
     * <pre>
     * Для инвалидации ранее выданных авторизационных токенов пользователя необходимо передать текущий авторизационный
     * токен. Инвалидируются все выданные токены, в том числе переданный, если указан параметр revoke_binding_token.
     * </pre>
     *
     * <strong>Возможные варианты ошибок</strong>
     * <pre>
     * invalid_token - передан невалидный токен
     * </pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Токен",
     *  output={
     *      "class"="Auth\ApiBundle\Dto\Controller\Access\Response\RevokeDto",
     *      "parsers"={"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *  },
     *  statusCodes={
     *      200="Успешное выполнение запроса",
     *      400="Невалидный запрос"
     *  }
     * )
     *
     * @param RevokeRequest $requestDto
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Method("POST")
     * @Route("/revoke")
     *
     * @ParamConverter("requestDto", class="Auth\ApiBundle\Dto\Controller\Access\Request\RevokeDto")
     */
    public function revokeAction(RevokeRequest $requestDto)
    {
        $revokeResultDto = $this->userTokenService->revokeTokenListByHash(
            $requestDto->getToken(),
            $requestDto->isRevokeBindingToken()
        );

        $response = new RevokeResponse();
        $response->setOk(!$revokeResultDto->getErrorList());
        $response->setErrorList($revokeResultDto->getErrorList());
        $response->setWarningList($revokeResultDto->getWarningList());

        return $this->buildResponse($response, 600);
    }
}