<?php

$sql[] = 'ALTER TABLE extension ADD COLUMN logo VARCHAR(150) NOT NULL AFTER name';
$sql[] = 'ALTER TABLE extension ADD COLUMN price DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0.00';
$sql[] = 'DROP TABLE IF EXISTS extension_screenshot';
$sql[] = '
CREATE TABLE IF NOT EXISTS extension_screenshot (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    extension_id INT NOT NULL,
    image VARCHAR(500) NOT NULL,
    INDEX idx_fk_screenshot_extension (extension_id),
    CONSTRAINT fk_screenshot_extension
        FOREIGN KEY (extension_id)
            REFERENCES extension ( id )
                ON DELETE CASCADE
) ENGINE = InnoDB
';