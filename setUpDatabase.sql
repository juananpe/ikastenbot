CREATE TABLE IF NOT EXISTS `milestone`
(
    `id`                    INT         NOT NULL AUTO_INCREMENT,
    `user_id`               BIGINT      NOT NULL,
    `milestone_name`        varchar(50) NULL,
    `milestone_start_date`  datetime    NOT NULL,
    `milestone_finish_date` datetime    NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
