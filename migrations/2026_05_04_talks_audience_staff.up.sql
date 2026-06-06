-- Phase Privacy admin : ajout d'un audience pour différencier les salons publics ouverts à tous
-- vs les salons internes au staff (visibles uniquement par les admins, ex : ancienne chatbox "Admin").
ALTER TABLE nf_talks
	ADD COLUMN audience ENUM('all', 'staff') NOT NULL DEFAULT 'all' AFTER type,
	ADD INDEX idx_talks_audience (audience);

-- Le talk "Admin" historique (legacy chatbox staff) passe en audience='staff' s'il existe.
UPDATE nf_talks SET audience = 'staff' WHERE name = 'Admin' AND type = 'public';
