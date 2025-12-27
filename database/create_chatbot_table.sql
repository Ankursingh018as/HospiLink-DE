<?php
/**
 * Create chatbot_logs table for tracking conversations
 */

USE hospilink;

-- Create chatbot logs table
CREATE TABLE IF NOT EXISTS chatbot_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    is_emergency TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_emergency (is_emergency)
) ENGINE=InnoDB;

SELECT 'Chatbot logs table created successfully!' as Status;
