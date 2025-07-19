

-- Tabela pracowników
CREATE TABLE pracownicy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imie VARCHAR(50) NOT NULL,
    aktywny BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Wstawienie pracowników
INSERT INTO pracownicy (imie) VALUES 
('Wiola'),
('Kamila'),
('Beata'),
('Dawid');

-- Tabela wizyt
CREATE TABLE wizyty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_wizyty DATE NOT NULL,
    godzina_rozpoczecia TIME NOT NULL,
    godzina_zakonczenia TIME NOT NULL,
    imie_klienta VARCHAR(100) NOT NULL,
    imie_zwierzaka VARCHAR(50) NOT NULL,
    telefon VARCHAR(20) NOT NULL,
    nazwa_uslugi VARCHAR(200) NOT NULL,
    pracownik_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id),
    INDEX idx_data_godzina (data_wizyty, godzina_rozpoczecia),
    INDEX idx_pracownik_data (pracownik_id, data_wizyty)
);

-- Przykładowe dane testowe
INSERT INTO wizyty (data_wizyty, godzina_rozpoczecia, godzina_zakonczenia, imie_klienta, imie_zwierzaka, telefon, nazwa_uslugi, pracownik_id) VALUES
('2025-07-21', '09:00:00', '10:30:00', 'Anna Kowalska', 'Max', '123456789', 'Strzyżenie + kąpiel', 1),
('2025-07-21', '11:00:00', '12:00:00', 'Jan Nowak', 'Bella', '987654321', 'Strzyżenie pazurów', 2),
('2025-07-22', '10:00:00', '11:30:00', 'Maria Wiśniewska', 'Rex', '555666777', 'Pełna pielęgnacja', 1),
('2025-07-22', '14:00:00', '15:00:00', 'Piotr Zieliński', 'Luna', '111222333', 'Kąpiel + suszenie', 3);