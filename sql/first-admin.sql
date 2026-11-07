-- Run once in phpMyAdmin (SQL tab) to create the first admin. All later admins are promoted from the dashboard Users page.
-- 1. Upload make-hash.php, open it in the browser, type the password you want, and copy the hash it shows. Then delete make-hash.php.
-- 2. Replace the values below (keep the quotes), then run it.
INSERT INTO users (full_name, email, student_id, phone, program, level, password_hash, role)
VALUES ('Youssif Alam Aldeen', 'www.yousifalm@gamil.com', '43191', '+249960945181', 'computer-science', 5, 'PASTE_THE_HASH_HERE', 'admin');
