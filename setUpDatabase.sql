CREATE TABLE IF NOT EXISTS `milestone`
(
    `id`                    INT         NOT NULL AUTO_INCREMENT,
    `chat_id`               BIGINT      NOT NULL,
    `milestone_name`        varchar(50) NULL,
    `milestone_date`        datetime    NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_user_id` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
