package ru.rabbitsfoot.domain;

import java.util.List;

import org.springframework.security.core.GrantedAuthority;

public class Role implements GrantedAuthority {
    private static final long serialVersionUID = 1L;

    private String name;
    private List<Privilege> privilegeList;

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    @Override
    public String getAuthority() {
        return this.name;
    }

    public List<Privilege> getPrivileges() {
        return privilegeList;
    }

    public void setPrivileges(List<Privilege> privilegeList) {
        this.privilegeList = privilegeList;
    }
}