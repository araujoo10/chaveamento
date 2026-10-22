<?php
/**
 * Conexão com o Banco de Dados via PDO
 * Suporta MySQL e Fallback SQLite transparente
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function connect(): void {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Tenta conexão primária com MySQL
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            // Timeout de conexão curto para não travar se o MySQL não estiver rodando
            $mysqlOptions = $options + [
                PDO::ATTR_TIMEOUT => 2,
            ];

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $mysqlOptions);
            self::$driver = 'mysql';
        } catch (PDOException $e) {
            // Se o banco MySQL não existir, tenta criar
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $rootDsn = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
                    $tmpPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                    $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                    self::$driver = 'mysql';
                    self::initializeSchema(self::$instance, 'mysql');
                    return;
                } catch (Exception $ex) {
                    // Segue para fallback
                }
            }

            // Fallback para SQLite embutido (permite execução imediata e desenvolvimento autônomo)
            try {
                $sqlitePath = __DIR__ . '/chaveamento.sqlite';
                self::$instance = new PDO('sqlite:' . $sqlitePath, null, null, $options);
                // Ativar foreign keys no SQLite
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$driver = 'sqlite';
            } catch (PDOException $sqliteEx) {
                die("Erro crítico de conexão com o banco de dados: " . $sqliteEx->getMessage());
            }
        }

        // Garante que o schema de tabelas esteja criado
        self::initializeSchema(self::$instance, self::$driver);
    }

    /**
     * Inicializa as tabelas se ainda não existirem
     */
    private static function initializeSchema(PDO $pdo, string $driver): void {
        if ($driver === 'mysql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS usuarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    email VARCHAR(150) NOT NULL UNIQUE,
                    senha VARCHAR(255) NOT NULL,
                    tipo ENUM('admin', 'organizador') DEFAULT 'admin',
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS torneios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(150) NOT NULL,
                    modalidade VARCHAR(80) NOT NULL,
                    formato VARCHAR(50) DEFAULT 'mata_mata',
                    descricao TEXT NULL,
                    status ENUM('planejado', 'em_andamento', 'finalizado') DEFAULT 'planejado',
                    criado_por INT NOT NULL,
                    campeao_id INT NULL,
                    vice_id INT NULL,
                    terceiro_id INT NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    finalizado_em DATETIME NULL,
                    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS participantes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    torneio_id INT NOT NULL,
                    nome VARCHAR(100) NOT NULL,
                    numero_semente INT NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (torneio_id) REFERENCES torneios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS partidas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    torneio_id INT NOT NULL,
                    rodada INT NOT NULL,
                    nome_rodada VARCHAR(50) NOT NULL,
                    ordem_partida INT NOT NULL,
                    participante1_id INT NULL,
                    participante2_id INT NULL,
                    placar1 INT NULL,
                    placar2 INT NULL,
                    penaltis1 INT NULL,
                    penaltis2 INT NULL,
                    vencedor_id INT NULL,
                    status ENUM('pendente', 'em_andamento', 'finalizada', 'bye') DEFAULT 'pendente',
                    proxima_partida_id INT NULL,
                    proximo_slot VARCHAR(20) NULL,
                    atualizado_por INT NULL,
                    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (torneio_id) REFERENCES torneios(id) ON DELETE CASCADE,
                    FOREIGN KEY (participante1_id) REFERENCES participantes(id) ON DELETE SET NULL,
                    FOREIGN KEY (participante2_id) REFERENCES participantes(id) ON DELETE SET NULL,
                    FOREIGN KEY (vencedor_id) REFERENCES participantes(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS logs_auditoria (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NULL,
                    usuario_nome VARCHAR(100) NULL,
                    torneio_id INT NULL,
                    acao VARCHAR(60) NOT NULL,
                    descricao TEXT NOT NULL,
                    dados_anteriores TEXT NULL,
                    dados_novos TEXT NULL,
                    ip VARCHAR(45) NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (torneio_id) REFERENCES torneios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // Schema SQLite
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS usuarios (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    email TEXT NOT NULL UNIQUE,
                    senha TEXT NOT NULL,
                    tipo TEXT DEFAULT 'admin',
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS torneios (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    modalidade TEXT NOT NULL,
                    formato TEXT DEFAULT 'mata_mata',
                    descricao TEXT,
                    status TEXT DEFAULT 'planejado',
                    criado_por INTEGER NOT NULL,
                    campeao_id INTEGER,
                    vice_id INTEGER,
                    terceiro_id INTEGER,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    finalizado_em DATETIME,
                    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS participantes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    torneio_id INTEGER NOT NULL,
                    nome TEXT NOT NULL,
                    numero_semente INTEGER,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (torneio_id) REFERENCES torneios(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS partidas (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    torneio_id INTEGER NOT NULL,
                    rodada INTEGER NOT NULL,
                    nome_rodada TEXT NOT NULL,
                    ordem_partida INTEGER NOT NULL,
                    participante1_id INTEGER,
                    participante2_id INTEGER,
                    placar1 INTEGER,
                    placar2 INTEGER,
                    penaltis1 INTEGER,
                    penaltis2 INTEGER,
                    vencedor_id INTEGER,
                    status TEXT DEFAULT 'pendente',
                    proxima_partida_id INTEGER,
                    proximo_slot TEXT,
                    atualizado_por INTEGER,
                    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (torneio_id) REFERENCES torneios(id) ON DELETE CASCADE,
                    FOREIGN KEY (participante1_id) REFERENCES participantes(id) ON DELETE SET NULL,
                    FOREIGN KEY (participante2_id) REFERENCES participantes(id) ON DELETE SET NULL,
                    FOREIGN KEY (vencedor_id) REFERENCES participantes(id) ON DELETE SET NULL
                );

                CREATE TABLE IF NOT EXISTS logs_auditoria (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    usuario_id INTEGER,
                    usuario_nome TEXT,
                    torneio_id INTEGER,
                    acao TEXT NOT NULL,
                    descricao TEXT NOT NULL,
                    dados_anteriores TEXT,
                    dados_novos TEXT,
                    ip TEXT,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (torneio_id) REFERENCES torneios(id) ON DELETE CASCADE
                );
            ");
        }

        // Cria usuário administrador padrão caso não exista
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        if ($total === 0) {
            $adminSenhaHash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmtAdmin = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo) 
                VALUES (:nome, :email, :senha, 'admin')
            ");
            $stmtAdmin->execute([
                ':nome' => 'Administrador Geral',
                ':email' => 'admin@etec.sp.gov.br',
                ':senha' => $adminSenhaHash
            ]);
        }
    }
}

// Função global para obtenção da conexão PDO
function getDB(): PDO {
    return Database::getConnection();
}
