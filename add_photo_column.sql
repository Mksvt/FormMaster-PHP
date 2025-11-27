-- SQL скрипт для додавання колонки Photo до таблиці objects

USE architecturalworkshop;

-- Додати колонку Photo, якщо вона ще не існує
ALTER TABLE objects 
ADD COLUMN IF NOT EXISTS Photo VARCHAR(255);

-- Перевірка структури таблиці
DESCRIBE objects;
