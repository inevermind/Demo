package ru.rabbitsfoot.service;

import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.core.userdetails.UsernameNotFoundException;
import org.springframework.dao.DataAccessException;

import ru.rabbitsfoot.domain.User;
import ru.rabbitsfoot.dao.UserDAO;

@Service
public class UserServiceImpl implements UserService, UserDetailsService {
    @Autowired
    private UserDAO userDAO;

    @Transactional
    public User getByEmail(String email) {
        return userDAO.getByEmail(email);
    }

    @Transactional
    public List<User> list() {
        return userDAO.list();
    }

    @Override
    @Transactional
    public User loadUserByUsername(String username) throws UsernameNotFoundException, DataAccessException {
        User user = userDAO.getByEmail(username);

        if(user == null) {
            throw new UsernameNotFoundException("Пользователь не найден");
        }

        return user;
    }
}
