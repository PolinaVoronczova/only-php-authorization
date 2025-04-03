<?php 

namespace Controllers\User;

use DateTime;
use Exception;

class User
{
    private $pdo = null;
    private ?int $id = null;
    private ?string $name = null;
    private ?string $phone = null;
    private ?string $email = null;
    private ?string $password = null;
    private $created_at = null;
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        string $name,
        string $phone,
        string $email,
        string $password
    )
    {
        $this->setName($name);
        $this->setPhone($phone);
        $this->setEmail($email);
        $this->setPassword($password);
        return $this->store();
    }
    public function store()
    {
        $now = new DateTime();
        $this->setCreatedTime($now->format('Y-m-d H:i:s'));
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, phone, email, password, created_at) 
            values ( :name, :phone, :email, :password, :created_at)"
            );
        $stmt->execute([
            'name' => $this->getName(),
            'phone' =>$this->getPhone(),
            'email' => $this->getEmail(),
            'password' => $this->getPassword(),
            'created_at' => $this->getCreatedTime()
        ]);
        $this->setId($this->pdo->lastInsertId());
        return true;
    }
    public function edit(
        string $name,
        string $phone,
        string $email,
        string $password
    )
    {
        $this->setName($name);
        $this->setPhone($phone);
        $this->setEmail($email);
        $this->setPassword($password);
        return $this->update();
    }
    public function update()
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET
                name = :name,
                phone = :phone,
                email = :email,
                password = :password
            WHERE id = :user_id"
        );
        $stmt->execute([
            'name' => $this->getName(),
            'phone' =>$this->getPhone(),
            'email' => $this->getEmail(),
            'password' => $this->getPassword(),
            'user_id' => $this->getId()
        ]);
        return true;
    }
    public function delete()
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $this->getId()]);
    }
    private function setId(int $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
    public function setPhone(string $phone)
    {
        $this->phone = $phone;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    private function setCreatedTime(mixed $createdTime)
    {
        $this->created_at = $createdTime;
    }

    public function getCreatedTime()
    {
        return $this->created_at;
    }

    public function findBy(string $colName, string $value, bool $setUser)
    {
        $findedCol = ['id', 'name', 'phone', 'email'];

        if (!in_array($colName, $findedCol)) {
            throw new Exception("Поиск по недопустимому столбцу.");
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE {$colName} = :valueCol");
        $stmt->execute(['valueCol' => $value]); 
        $user = $stmt->fetch();
        if ($user && $setUser === true) {
            $this->setName($user['name']);
            $this->setPhone($user['phone']);
            $this->setEmail($user['email']);
            $this->setPassword($user['password']);
            $this->setId($user['id']);
            $this->setCreatedTime($user['created_at']);
            return true;
        } else {
            return $user ? true : false;
        }
    }
}