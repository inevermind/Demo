package ru.rabbitsfoot.dao;

import java.util.List;

import org.hibernate.Session;
import org.hibernate.SessionFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Repository;
import org.hibernate.criterion.Restrictions;

import ru.rabbitsfoot.domain.User;

@Repository
public class UserDAOImpl implements UserDAO {
    @Autowired
    private SessionFactory sessionFactory;


    public void setSessionFactory(SessionFactory sessionFactory) {
        this.sessionFactory = sessionFactory;
    }

    public Session openSession() {
        return sessionFactory.getCurrentSession();
    }

    public User getByEmail(String email) {
        return (User)sessionFactory.getCurrentSession().createCriteria(User.class).add(
                Restrictions.naturalId().set("email", email)
        ).uniqueResult();
    }

    @SuppressWarnings("unchecked")
    public List<User> list() {
        return sessionFactory.getCurrentSession().createQuery("from User").list();
    }
}
