-- Add PT/OPT result column to bookings table
-- Run this on the production database

ALTER TABLE bookings 
ADD COLUMN pt_opt_result VARCHAR(255) NULL 
AFTER teacher_notes;
