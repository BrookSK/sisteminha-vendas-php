-- Seed initial technical areas for documentations
START TRANSACTION;
INSERT IGNORE INTO documentation_areas (name, created_at) VALUES
('Sites da Brasiliana', NOW()),
('Vendas Brasiliana', NOW()),
('Desenvolvimento Web', NOW()),
('E-commerce e Plataformas', NOW()),
('Servidores Cloud', NOW()),
('Hospedagem de E-mail', NOW()),
('Hospedagem de Site', NOW()),
('Design, UX e UI', NOW()),
('WordPress Avançado', NOW()),
('Sistemas Brasiliana', NOW()),
('Automações e Emuladores', NOW()),
('IA e Chatbot', NOW()),
('Interações e APIs', NOW()),
('Desenvolvimento Mobile', NOW()),
('Gestão de Projetos e Processos', NOW()),
('Sites e Landing Pages', NOW()),
('WordPress Básico', NOW()),
('Notion e Consultorias', NOW()),
('Segurança e Compilers', NOW());
COMMIT;
