-- MediFollow database structure — assignment section 4.
-- This file creates tables only; it does not insert or remove records.
CREATE TABLE IF NOT EXISTS patient (
 patient_id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 phone_number VARCHAR(15) NULL DEFAULT NULL,
 ic_number CHAR(12) NULL DEFAULT NULL UNIQUE,
 pin VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS doctor (
 doctor_id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(100) NOT NULL,
 password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS clinic_staff (
 staff_id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(100) NOT NULL,
 password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS follow_up (
 followup_id INT AUTO_INCREMENT PRIMARY KEY,
 patient_id INT NOT NULL,
 doctor_id INT NOT NULL,
 followup_date DATE NOT NULL,
 followup_time TIME NOT NULL,
 purpose VARCHAR(255) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'Upcoming',
 CONSTRAINT fk_followup_patient FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
 CONSTRAINT fk_followup_doctor FOREIGN KEY (doctor_id) REFERENCES doctor(doctor_id),
 INDEX idx_followup_date (followup_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS aftercare_instruction (
 aftercare_id INT AUTO_INCREMENT PRIMARY KEY,
 followup_id INT NOT NULL,
 doctor_id INT NOT NULL,
 instruction TEXT NOT NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_aftercare_followup FOREIGN KEY (followup_id) REFERENCES follow_up(followup_id),
 CONSTRAINT fk_aftercare_doctor FOREIGN KEY (doctor_id) REFERENCES doctor(doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS patient_status_update (
 update_id INT AUTO_INCREMENT PRIMARY KEY,
 followup_id INT NOT NULL,
 `condition` VARCHAR(30) NOT NULL,
 details TEXT NOT NULL,
 submitted_at DATETIME NOT NULL,
 CONSTRAINT fk_update_followup FOREIGN KEY (followup_id) REFERENCES follow_up(followup_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS clinic_contact_request (
 request_id INT AUTO_INCREMENT PRIMARY KEY,
 update_id INT NOT NULL,
 request_status VARCHAR(30) NOT NULL,
 requested_at DATETIME NOT NULL,
 CONSTRAINT fk_request_update FOREIGN KEY (update_id) REFERENCES patient_status_update(update_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Additive migration: existing written instructions and clinic records are unchanged.
CREATE TABLE IF NOT EXISTS aftercare_audio (
 audio_id INT AUTO_INCREMENT PRIMARY KEY,
 aftercare_id INT NULL,
 followup_id INT NOT NULL,
 doctor_id INT NOT NULL,
 storage_key CHAR(64) NOT NULL UNIQUE,
 mime_type VARCHAR(30) NOT NULL,
 byte_size INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_audio_entry FOREIGN KEY (aftercare_id) REFERENCES aftercare_instruction(aftercare_id),
 INDEX idx_audio_entry (aftercare_id),
 CONSTRAINT fk_audio_followup FOREIGN KEY (followup_id) REFERENCES follow_up(followup_id),
 CONSTRAINT fk_audio_doctor FOREIGN KEY (doctor_id) REFERENCES doctor(doctor_id),
 INDEX idx_audio_followup (followup_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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
