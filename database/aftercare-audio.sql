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
