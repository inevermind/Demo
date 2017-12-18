package ru.rabbitsfoot.service;

import ru.rabbitsfoot.domain.User;

import java.util.List;

public interface UserService {
    public User getByEmail(String email);
    public List<User> list();
}
