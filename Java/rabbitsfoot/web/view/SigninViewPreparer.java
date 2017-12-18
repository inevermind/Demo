package ru.rabbitsfoot.web.view;

import org.apache.tiles.preparer.PreparerException;
import org.apache.tiles.preparer.ViewPreparer;
import org.apache.tiles.request.Request;
import org.apache.tiles.AttributeContext;
import org.apache.tiles.Attribute;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Controller;

@Controller
@Scope("sessions")
public class SigninViewPreparer implements ViewPreparer {
    public void execute(Request tilesRequest, AttributeContext attributeContext) throws PreparerException {
        attributeContext.putAttribute(
                "body",
                new Attribute("This is the value added by the ViewPreparer")
        );
    }
}
