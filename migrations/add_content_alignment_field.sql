-- Migration: Add content_alignment field to form_customizations table
-- Date: 2026-01-14
-- Description: Adds a content_alignment field to allow users to choose between left, center, or right alignment for form content

-- Add content_alignment column
ALTER TABLE form_customizations
ADD COLUMN IF NOT EXISTS content_alignment VARCHAR(10) DEFAULT 'center';

-- Update existing records to have the default value
UPDATE form_customizations
SET content_alignment = 'center'
WHERE content_alignment IS NULL;
