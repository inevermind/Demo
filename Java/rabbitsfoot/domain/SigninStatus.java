package ru.rabbitsfoot.domain;

public class SigninStatus {
    private Boolean signedIn = false;
    private String email;

    public SigninStatus() {}

    public SigninStatus(Boolean signedIn, String email) {
        this.signedIn = signedIn;
        this.email = email;
    }

    public Boolean isSignedIn() {
        return signedIn;
    }

    public void setSignedIn(Boolean signedIn) {
        this.signedIn = signedIn;
    }

    public String getEmail() {
        return this.email;
    }

    public void setEmail(String email) {
        this.email = email;
    }
}
