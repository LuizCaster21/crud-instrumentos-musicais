-- Tabela de Usuários (para autenticação JWT)
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Instrumentos
CREATE TABLE IF NOT EXISTS instrumentos (
    id SERIAL PRIMARY KEY,
    usuario_id INT REFERENCES usuarios(id) ON DELETE CASCADE,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL, -- Ex: Cordas, Sopro, Percussão
    preco DECIMAL(10,2) NOT NULL CHECK (preco >= 0),
    quantidade_estoque INT NOT NULL DEFAULT 0 CHECK (quantidade_estoque >= 0),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Amplificadores
CREATE TABLE IF NOT EXISTS amplificadores (
    id SERIAL PRIMARY KEY,
    usuario_id INT REFERENCES usuarios(id) ON DELETE CASCADE,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL, -- Ex: Valvulado, Transistorizado, Híbrido
    potencia_watts INT NOT NULL CHECK (potencia_watts >= 0),
    preco DECIMAL(10,2) NOT NULL CHECK (preco >= 0),
    quantidade_estoque INT NOT NULL DEFAULT 0 CHECK (quantidade_estoque >= 0),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Pedais e Pedaleiras
CREATE TABLE IF NOT EXISTS pedais_efeitos (
    id SERIAL PRIMARY KEY,
    usuario_id INT REFERENCES usuarios(id) ON DELETE CASCADE,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    tipo_efeito VARCHAR(100) NOT NULL, -- Ex: Distortion, Delay, Multi-efeitos
    tecnologia VARCHAR(50), -- Ex: Analógico, Digital
    preco DECIMAL(10,2) NOT NULL CHECK (preco >= 0),
    quantidade_estoque INT NOT NULL DEFAULT 0 CHECK (quantidade_estoque >= 0),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para otimização de consultas por usuário
CREATE INDEX IF NOT EXISTS idx_instrumentos_usuario_id ON instrumentos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_amplificadores_usuario_id ON amplificadores(usuario_id);
CREATE INDEX IF NOT EXISTS idx_pedais_efeitos_usuario_id ON pedais_efeitos(usuario_id);