DROP USER IF EXISTS 'uczen1'@'localhost';
DROP USER IF EXISTS 'uczen2'@'localhost';

CREATE USER 'uczen1'@'localhost' IDENTIFIED BY 'uczen1pass';
GRANT SELECT ON szkola.* TO 'uczen1'@'localhost';

CREATE USER 'uczen2'@'localhost' IDENTIFIED BY 'uczen2pass';
GRANT SELECT, INSERT, UPDATE, DELETE ON szkola.* TO 'uczen2'@'localhost';

FLUSH PRIVILEGES;
