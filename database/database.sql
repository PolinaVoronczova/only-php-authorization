DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id          bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name        varchar(255) UNIQUE,
    phone    varchar(36) UNIQUE,
    email    varchar(255) UNIQUE,
    password    varchar(255),
    created_at  timestamp
);