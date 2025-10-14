-- Add case_type and case_stage fields to cases table
ALTER TABLE cases 
ADD COLUMN case_type VARCHAR(50) DEFAULT NULL COMMENT 'نوع پرونده: اظهارنامه، دادخواست بدوی، اعاده دادرسی',
ADD COLUMN case_stage VARCHAR(50) DEFAULT NULL COMMENT 'مرحله پرونده بر اساس نوع';

-- Update existing records to have default values
UPDATE cases SET case_type = NULL, case_stage = NULL WHERE case_type IS NULL;
