-- Optional private messaging, scoped to an existing follow-up.
CREATE TABLE IF NOT EXISTS followup_message (
 message_id INT AUTO_INCREMENT PRIMARY KEY,
 followup_id INT NOT NULL,
 sender_role ENUM('patient','doctor','clinic_staff') NOT NULL,
 sender_id INT NOT NULL,
 sender_name VARCHAR(100) NOT NULL,
 body TEXT NOT NULL,
 client_nonce CHAR(32) NOT NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_message_followup FOREIGN KEY (followup_id) REFERENCES follow_up(followup_id),
 UNIQUE KEY idx_message_retry (sender_role,sender_id,client_nonce),
 INDEX idx_message_thread (followup_id,message_id),
 INDEX idx_message_rate (sender_role,sender_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS followup_chat_read (
 followup_id INT NOT NULL,
 reader_role ENUM('patient','doctor','clinic_staff') NOT NULL,
 reader_id INT NOT NULL,
 last_message_id INT NOT NULL DEFAULT 0,
 PRIMARY KEY (followup_id,reader_role,reader_id),
 CONSTRAINT fk_chat_read_followup FOREIGN KEY (followup_id) REFERENCES follow_up(followup_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff-mediated doctor review; separate from follow-up completion.
CREATE TABLE IF NOT EXISTS followup_doctor_review (
 followup_id INT PRIMARY KEY,
 review_status ENUM('Pending','Sent to doctor','Resolved') NOT NULL DEFAULT 'Pending',
 patient_note VARCHAR(2000) NOT NULL DEFAULT '',
 requested_at DATETIME NOT NULL,
 referred_doctor_id INT NULL,
 referred_by INT NULL,
 referred_at DATETIME NULL,
 resolved_at DATETIME NULL,
 CONSTRAINT fk_review_followup FOREIGN KEY (followup_id) REFERENCES follow_up(followup_id),
 CONSTRAINT fk_review_doctor FOREIGN KEY (referred_doctor_id) REFERENCES doctor(doctor_id),
 CONSTRAINT fk_review_staff FOREIGN KEY (referred_by) REFERENCES clinic_staff(staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
