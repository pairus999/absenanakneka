CREATE TABLE IF NOT EXISTS device_status (
 id INT NOT NULL PRIMARY KEY,
 device_name VARCHAR(100) NOT NULL DEFAULT 'ESP32 Fingerprint',
 online TINYINT(1) NOT NULL DEFAULT 0,
 last_seen DATETIME NULL,
 ip_address VARCHAR(45) NULL
);
INSERT INTO device_status (id,device_name,online) VALUES (1,'ESP32 Fingerprint',0)
ON DUPLICATE KEY UPDATE device_name=VALUES(device_name);

CREATE TABLE IF NOT EXISTS device_commands (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 command_type VARCHAR(30) NOT NULL,
 fingerprint_id INT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
 result_message VARCHAR(255) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 INDEX idx_status(status), INDEX idx_fp(fingerprint_id)
);
