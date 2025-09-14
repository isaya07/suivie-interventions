-- Migration pour ajouter les coordonnées GPS aux clients
ALTER TABLE clients
ADD COLUMN latitude DECIMAL(10,8) NULL COMMENT 'Latitude GPS du client',
ADD COLUMN longitude DECIMAL(11,8) NULL COMMENT 'Longitude GPS du client';

-- Index pour les requêtes géographiques (optionnel)
CREATE INDEX idx_clients_gps ON clients(latitude, longitude);