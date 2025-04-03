<?

namespace Controllers;

class PdoConnection
{
    private $pdo;
    public function connect(): \PDO
    {
        $params = parse_ini_file('../src/database.ini');
        if ($params === false) {
            throw new \Exception("Проверьте файл конфигурации database.ini.");
        }

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['password']
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo = $pdo;
    }

    public function getConnection()
    {
        if (isset($this->pdo)) {
            return $this->pdo;
        } else {
            throw new \Exception("Соединение не установленно.");
        }
    }
}