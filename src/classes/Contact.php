<?php

namespace CT275\Labs;

use PDO;

class Contact
{
  private ?PDO $db;

  public int $id = -1;
  public $name;
  public $phone;
  public $notes;
  public $avatar;
  public $created_at;
  public $updated_at;

  public function __construct(?PDO $pdo)
  {
    $this->db = $pdo;
  }

  public function fill(array $data): Contact
  {
    $this->name = $data['name'] ?? '';
    $this->phone = $data['phone'] ?? '';
    $this->notes = $data['notes'] ?? '';
    $this->avatar = $data['avatar'] ?? '';
    return $this;
  }

  public function validate(array $data): array
  {
    $errors = [];

    $name = trim($data['name'] ?? '');
    if (!$name) {
      $errors['name'] = 'Invalid name.';
    }

    $validPhone = preg_match(
      '/^(03|05|07|08|09|01[2|6|8|9])+([0-9]{8})\b$/',
      $data['phone'] ?? ''
    );
    if (!$validPhone) {
      $errors['phone'] = 'Invalid phone number.';
    }

    $notes = trim($data['notes'] ?? '');
    if (strlen($notes) > 255) {
      $errors['notes'] = 'Notes must be at most 255 characters.';
    }

    return $errors;
  }

  public function all() : array 
  {
      $contacts = [];

      $statement = $this->db->prepare('SELECT * FROM contacts');
      $statement->execute();
      while($row = $statement->fetch()) {
         $contact = new Contact($this->db);
         $contact->fillFromDbRow($row);
         $contacts[] = $contact;
      }

      return $contacts;
  }

  protected function fillFromDbRow(array $row): Contact
  {
      $this->id = $row['id'];
      $this->name = $row['name'];
      $this->phone = $row['phone'];
      $this->notes = $row['notes'];
      $this->avatar = $row['avatar'];
      $this->created_at = $row['created_at'];
      $this->updated_at = $row['updated_at'];

      return $this;
  }

  public function count(): int 
  {
      $statement = $this->db->prepare('SELECT COUNT(*) FROM contacts');
      $statement->execute();
      return $statement->fetchColumn();
  }

  public function paginate(int $offset = 0, int $limit = 10): array
  {
      $contacts = [];
      $statement = $this->db->prepare('SELECT * FROM contacts ORDER BY id ASC limit :limit offset :offset');
      $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
      $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
      $statement->execute();
      while ($row = $statement->fetch()) {
         $contact = new Contact($this->db);
         $contact->fillFromDbRow($row);
         $contacts[] = $contact;
      }

      return $contacts;
  }

  public function save(): bool 
  {
   $result = false;

   if ($this->id >= 0) {
      $statement = $this->db->prepare(
         'UPDATE contacts SET name = :name,
            phone = :phone, notes = :notes, avatar = :avatar, updated_at = now()
            WHERE id = :id'
      );
      $result = $statement->execute([
         'name' => $this->name,
         'phone' => $this->phone,
         'notes' => $this->notes,
         'avatar' => $this->avatar,
         'id' => $this->id]);
   } else {
      $statement = $this->db->prepare(
         'INSERT INTO contacts (name, phone, notes, avatar, created_at, updated_at)
            VALUES (:name, :phone, :notes, :avatar, now(), now())'
      );
      $result = $statement->execute([
         'name' => $this->name,
         'phone' => $this->phone,
         'notes' => $this->notes,
         'avatar' => $this->avatar
      ]);
      if ($result) {
         $this->id = $this->db->lastInsertId();
      }
   }

   return $result;
  }

  public function find(int $id) : ?Contact 
  {
   $statement = $this->db->prepare('SELECT * FROM contacts WHERE id = :id');
   $statement->execute(['id' => $id]);

   if ($row = $statement->fetch()) {
      $this->fillFromDbRow($row);
      return $this;
   }

   return null;
  }

  public function delete(): bool 
  {
   $statement = $this->db->prepare('DELETE FROM contacts WHERE id = :id');
   return $statement->execute(['id' => $this->id]);
  }

  public function upload_avatar(string $input_name, string $target_dir, array $errors, string $root_path): string 
  {
      if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
        return ''; 
      }

      $file = $_FILES[$input_name];
   
      $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
      if (!in_array($file['type'], $allowed_types)) {
         $errors['avatar'] = 'File không phải là định dạng ảnh hợp lệ (JPG, PNG, GIF).';
         return '';
      }

      if ($file['size'] > 5000000) {
         $errors['avatar'] = 'Kích thước ảnh quá lớn (tối đa 5MB).';
         return '';
      }

      $upload_path = $root_path . '/public' . $target_dir;

      if (!is_dir($upload_path)) {
         mkdir($upload_path, 0777, true);
      }

      $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $new_file_name = uniqid('avatar_') . '.' . $file_extension;
      $target_file = $upload_path . $new_file_name;

      if (move_uploaded_file($file['tmp_name'], $target_file)) {
         return $target_dir . $new_file_name;
      } else {
         $errors['avatar'] = 'Không thể di chuyển file đã upload.';
         return '';
      }
  }


  /**
     * Phương thức tĩnh (Factory Method) để tìm contact theo ID.
     * Trả về một đối tượng Contact MỚI hoặc null.
     */
    public static function findById(int $id, ?PDO $pdo) : ?Contact 
    {
        if (!$pdo) return null;
        $statement = $pdo->prepare('SELECT * FROM contacts WHERE id = :id');
        $statement->execute(['id' => $id]);

        if ($row = $statement->fetch()) {
            $contact = new Contact($pdo);
            $contact->fillFromDbRow($row);
            return $contact;
        }

        return null;
    }

    /**
     * Kiểm tra xem đối tượng Contact đã được lưu vào CSDL chưa.
     * Trả về true nếu là đối tượng mới (chưa có ID hợp lệ).
     */
    public function isNew(): bool
    {
        return $this->id < 0;
    }

    /**
     * Phương thức magic __toString() để dễ dàng in thông tin đối tượng (cho mục đích debug).
     */
    public function __toString(): string
    {
        return sprintf(
            'Contact [ID: %d, Name: %s, Phone: %s, Notes: %s]',
            $this->id,
            $this->name,
            $this->phone,
            substr($this->notes, 0, 50) . (strlen($this->notes) > 50 ? '...' : '')
        );
    } 

    /**
     * Tìm kiếm contacts dựa trên từ khóa trong cột 'name' hoặc 'phone'.
     * Hỗ trợ sắp xếp và phân trang.
     */
    public function search(string $keyword, int $offset = 0, int $limit = 10, string $orderBy = 'id', string $orderDir = 'ASC'): array
    {
        $contacts = [];
        $keyword = '%' . trim($keyword) . '%';

        // Danh sách các cột được phép sắp xếp để tránh SQL Injection
        $allowedColumns = ['id', 'name', 'phone', 'created_at', 'updated_at'];
        $orderDir = strtoupper($orderDir);

        if (!in_array($orderBy, $allowedColumns) || ($orderDir !== 'ASC' && $orderDir !== 'DESC')) {
            $orderBy = 'id';
            $orderDir = 'ASC';
        }

        $sql = "SELECT * FROM contacts 
                WHERE name LIKE :keyword OR phone LIKE :keyword
                ORDER BY {$orderBy} {$orderDir} 
                LIMIT :limit OFFSET :offset";

        $statement = $this->db->prepare($sql);
        
        // Binding parameters
        $statement->bindValue(':keyword', $keyword, PDO::PARAM_STR);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $statement->execute();

        while ($row = $statement->fetch()) {
            $contact = new Contact($this->db);
            $contact->fillFromDbRow($row);
            $contacts[] = $contact;
        }

        return $contacts;
    }

    /**
     * Đếm tổng số lượng records tìm thấy (khi có áp dụng tìm kiếm).
     */
    public function countSearch(string $keyword): int 
    {
        $keyword = '%' . trim($keyword) . '%';
        
        $sql = "SELECT COUNT(*) FROM contacts 
                WHERE name LIKE :keyword OR phone LIKE :keyword";
                
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':keyword', $keyword, PDO::PARAM_STR);
        $statement->execute();
        
        return $statement->fetchColumn();
    }

    /**
     * Xóa nhiều contacts cùng lúc bằng cách sử dụng Transaction.
     */
    public function deleteMultiple(array $ids): bool 
    {
        $this->db->beginTransaction();
        $success = true;

        try {
            $statement = $this->db->prepare('DELETE FROM contacts WHERE id = :id');
            
            foreach ($ids as $id) {
                $id = (int)$id; 
                
                if (!$statement->execute(['id' => $id])) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }

        } catch (\PDOException $e) {
            $this->db->rollBack();
            // throw $e; // Có thể ném lỗi để hàm gọi xử lý
            return false;
        }
    }

    // ----------------------------------------------------------------------
    // CHỨC NĂNG BỔ SUNG: 2. KIỂM TRA DUY NHẤT (DATABASE VALIDATION)
    // ----------------------------------------------------------------------

    /**
     * Kiểm tra xem số điện thoại có bị trùng lặp trong CSDL hay không.
     */
    public function isPhoneUnique(string $phone, int $ignoreId = -1): bool
    {
        $sql = 'SELECT COUNT(*) FROM contacts WHERE phone = :phone AND id != :ignoreId';
        $statement = $this->db->prepare($sql);
        
        $statement->execute([
            'phone' => $phone,
            'ignoreId' => $ignoreId
        ]);

        return $statement->fetchColumn() === 0;
    }

    // ----------------------------------------------------------------------
    // CHỨC NĂNG BỔ SUNG: 3. TRUY XUẤT TỔNG HỢP (AGGREGATION)
    // ----------------------------------------------------------------------

    /**
     * Đếm số lượng contacts theo đầu số điện thoại (2 chữ số đầu).
     */
    public function countByPhonePrefix(): array
    {
        $sql = "SELECT SUBSTRING(phone, 1, 2) AS prefix, COUNT(*) AS count 
                FROM contacts 
                GROUP BY prefix 
                HAVING LENGTH(prefix) = 2";

        $statement = $this->db->prepare($sql);
        $statement->execute();

        $results = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['prefix']] = (int)$row['count'];
        }

        return $results;
    }

/**
     * Lấy chi tiết một contact và tên loại của nó dựa trên ID.
     * @param int $id ID của contact cần truy vấn.
     * @param ?PDO $pdo Đối tượng PDO để kết nối CSDL.
     * @return ?object Một đối tượng chứa chi tiết contact hoặc null.
     */
    public static function getContactDetails(int $id, ?PDO $pdo): ?object
    {
        if (!$pdo) {
            return null;
        }

        $sql = "SELECT 
                    c.*, ct.name AS type_name 
                FROM contacts c
                INNER JOIN contact_types ct ON c.type_id = ct.id
                WHERE c.id = :id";
        
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        // Trả về một đối tượng duy nhất
        $result = $statement->fetch(PDO::FETCH_OBJ);

        return $result ?: null;
    }
}
