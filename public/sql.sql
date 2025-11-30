ALTER TABLE contacts
ADD CONSTRAINT fk_contact_type_manual -- Đặt tên cho ràng buộc
    FOREIGN KEY (type_id) 
    REFERENCES contact_types (id) -- Tham chiếu tới bảng và cột
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- Lưu ý: Nếu cột type_id của bạn chứa các giá trị không hợp lệ (không tồn tại trong contact_types.id), 
-- PostgreSQL sẽ báo lỗi khi thêm ràng buộc này.