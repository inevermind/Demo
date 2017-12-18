package ru.rabbitsfoot.dao;

import ru.rabbitsfoot.domain.User;

import java.util.List;

public interface UserDAO {
    public User getByEmail(String email);
    public List<User> list();
}
