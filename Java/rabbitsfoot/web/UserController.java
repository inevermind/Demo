package ru.rabbitsfoot.web;

import java.util.HashMap;
import java.util.Map;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.security.access.annotation.Secured;
import org.springframework.security.authentication.BadCredentialsException;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.authentication.AuthenticationManager;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.ResponseBody;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;

import ru.rabbitsfoot.domain.User;
import ru.rabbitsfoot.service.UserService;
import ru.rabbitsfoot.domain.SigninStatus;

@Controller
@RequestMapping("/user")
public class UserController {
    @Autowired
    private UserService userService;
    @Autowired
    @Qualifier("authenticationManager")
    AuthenticationManager authenticationManager;

    /*@Secured("ROLE_USER")*/
    @RequestMapping("/list")
    public String list(Map<String, Object> map) {
        map.put("user", new User());
        map.put("userList", userService.list());

        return "list";
    }

    @RequestMapping(value = "/signin", method = RequestMethod.POST)
    public @ResponseBody SigninStatus signin(@RequestParam("login") String login,
                                       @RequestParam("password") String password) {
        UsernamePasswordAuthenticationToken token = new UsernamePasswordAuthenticationToken(login, password);
        User user = new User();
        user.setEmail(login);
        token.setDetails(user);

        SigninStatus signinStatus = new SigninStatus();
        try {
            Authentication authentication = authenticationManager.authenticate(token);
            SecurityContextHolder.getContext().setAuthentication(authentication);
            signinStatus.setSignedIn(authentication.isAuthenticated());
            signinStatus.setEmail(authentication.getName());
        } catch (Exception e) {}

        return signinStatus;
    }

    @RequestMapping("/signout")
    public String signout() {
        return "signout";
    }
}
