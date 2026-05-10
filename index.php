<?php
require 'db.php';

$errors = [];
$success = '';

$languages = [
    'Pascal',
    'C',
    'C++',
    'JavaScript',
    'PHP',
    'Python',
    'Haskell',
    'Java',
    'Clojure',
    'Prolog',
    'Scala'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $selected_languages = $_POST['languages'] ?? [];
    $biography = trim($_POST['biography'] ?? '');
    $contract = isset($_POST['contract']);

    /*
     * ВАЛИДАЦИЯ
     */

    // ФИО
    if (
        empty($full_name) ||
        !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $full_name) ||
        mb_strlen($full_name) > 150
    ) {
        $errors[] = 'ФИО должно содержать только буквы и пробелы (не более 150 символов).';
    }

    // Телефон
    if (
        empty($phone) ||
        !preg_match('/^\+?[0-9\s\-]{7,20}$/', $phone)
    ) {
        $errors[] = 'Некорректный номер телефона.';
    }

    // Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email.';
    }

    // Дата рождения
    if (empty($birth_date)) {
        $errors[] = 'Укажите дату рождения.';
    }

    // Пол
    $allowed_genders = ['male', 'female'];

    if (!in_array($gender, $allowed_genders)) {
        $errors[] = 'Некорректное значение пола.';
    }

    // Языки программирования
    if (empty($selected_languages)) {
        $errors[] = 'Выберите хотя бы один язык программирования.';
    } else {
        foreach ($selected_languages as $lang) {
            if (!in_array($lang, $languages)) {
                $errors[] = 'Обнаружен недопустимый язык программирования.';
                break;
            }
        }
    }

    // Биография
    if (empty($biography)) {
        $errors[] = 'Поле биографии не может быть пустым.';
    }

    // Чекбокс
    if (!$contract) {
        $errors[] = 'Необходимо ознакомиться с контрактом.';
    }

    /*
     * СОХРАНЕНИЕ
     */

    if (empty($errors)) {

        // Добавление основной записи
        $stmt = $pdo->prepare("
            INSERT INTO applications
            (full_name, phone, email, birth_date, gender, biography, contract_accepted)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $full_name,
            $phone,
            $email,
            $birth_date,
            $gender,
            $biography,
            1
        ]);

        $application_id = $pdo->lastInsertId();

        // Добавление языков
        foreach ($selected_languages as $lang_name) {

            // Получаем ID языка
            $stmt = $pdo->prepare("
                SELECT id FROM programming_languages
                WHERE name = ?
            ");

            $stmt->execute([$lang_name]);

            $language_id = $stmt->fetchColumn();

            // Записываем связь
            $stmt = $pdo->prepare("
                INSERT INTO application_languages
                (application_id, language_id)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $application_id,
                $language_id
            ]);
        }

        $success = 'Данные успешно сохранены.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Форма</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>

    <header class="header">
        <div class="header__container container">
            <h1>Отправка формы</h1>
        </div>
    </header>

    <main>
        <div class="main__container container">
            <?php if (!empty($errors)): ?>
            <div style="color:red;">
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li>
                        <?= htmlspecialchars($error) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div style="color:green;">
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <form method="POST">

                <p>
                    ФИО:<br>
                    <input type="text" name="full_name" required>
                </p>

                <p>
                    Телефон:<br>
                    <input type="text" name="phone" required>
                </p>

                <p>
                    Email:<br>
                    <input type="email" name="email" required>
                </p>

                <p>
                    Дата рождения:<br>
                    <input type="date" name="birth_date" required>
                </p>

                <p>
                    Пол:<br>

                    <label class="radio_btn">
                        <input type="radio" name="gender" value="male" required>
                        Мужской
                    </label>

                    <label class="radio_btn">
                        <input type="radio" name="gender" value="female">
                        Женский
                    </label>
                </p>

                <p>
                    Любимые языки программирования:<br>

                    <select name="languages[]" multiple required size="11">

                        <?php foreach ($languages as $lang): ?>

                        <option value="<?= htmlspecialchars($lang) ?>">
                            <?= htmlspecialchars($lang) ?>
                        </option>

                        <?php endforeach; ?>

                    </select>
                </p>

                <p>
                    Биография:<br>

                    <textarea name="biography" rows="5" cols="40" required></textarea>
                </p>

                <p>
                    <label class="form_checkbox">
                        <input type="checkbox" name="contract" required>
                        С контрактом ознакомлен
                    </label>
                </p>

                <button type="submit">
                    Отправить форму
                </button>

            </form>
        </div>

    </main>

</body>

</html>