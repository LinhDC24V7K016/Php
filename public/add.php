<?php
require_once __DIR__ . '/../src/bootstrap.php';

include_once __DIR__ . '/../src/partials/header.php';

use CT275\Labs\Contact;

$errors = [];
const DEFAULT_AVATAR = '/uploads/default-avatar.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $contactData = [
      'name' => $_POST['name'] ?? '',
      'phone' => $_POST['phone'] ?? '',
      'notes' => $_POST['notes'] ?? '',
   ];
   
   $contact = new Contact($PDO);
   $errors = $contact->validate($contactData);
   $target_dir = '/uploads/';
   $root_path = __DIR__ . '/..';
   $avatar_path = $contact->upload_avatar('avatar', $target_dir, $errors, $root_path);

   if (empty($avatar_path) && !isset($errors['avatar'])) {
        $avatar_path = DEFAULT_AVATAR;
    }

   if (empty($errors)) {
      $contact->fill($contactData);
      $contact->avatar = $avatar_path;
      $contact->save() && redirect('/');
   }
}


$keyword = '0918';
$searchResults = $contactModel->search($keyword, 0, 10, 'name', 'DESC');
$totalResults = $contactModel->countSearch($keyword);
?>

<body>
  <?php include_once __DIR__ . '/../src/partials/navbar.php' ?>

  <!-- Main Page Content -->
  <div class="container">

    <?php
    $subtitle = 'Add your contacts here.';
    include_once __DIR__ . '/../src/partials/heading.php';
    ?>

    <div class="row">
      <div class="col-12 row justify-content-center">

        <div class="col-md-3 mt-3 me-5">            
          <?php
            $default_src = defined('DEFAULT_AVATAR') ? DEFAULT_AVATAR : '/path/to/placeholder.png'; 
            $avatar_display_src = $default_src; 
          ?>

          <img id="avatar-preview" 
            src="<?= html_escape($avatar_display_src) ?>" 
            alt="Avatar Preview" 
            class="img-fluid rounded-circle shadow-sm"
            style="width: 300px; height: 300px; object-fit: cover; display: block; margin: 0 auto;" />
        </div>

        <form method="post" class="col-md-6" enctype="multipart/form-data">

          <!-- Name -->
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" maxlen="255" id="name" placeholder="Enter Name" value="<?= isset($_POST['name']) ? html_escape($_POST['name']) : '' ?>" />

            <?php if (isset($errors['name'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['name'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Phone -->
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>" maxlen="255" id="phone" placeholder="Enter Phone" value="<?= isset($_POST['phone']) ? html_escape($_POST['phone']) : '' ?>" />

            <?php if (isset($errors['phone'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['phone'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label for="notes" class="form-label">Notes </label>
            <textarea name="notes" id="notes" class="form-control<?= isset($errors['notes']) ? ' is-invalid' : '' ?>" placeholder="Enter notes (maximum character limit: 255)"><?= isset($_POST['notes']) ? html_escape($_POST['notes']) : '' ?></textarea>

            <?php if (isset($errors['notes'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['notes'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Avatar -->
          <div class="mb-3">
            <label for="avatar" class="form-label">Avatar </label>
            <input type="file" name="avatar" class="form-control<?= isset($errors['avatar']) ? ' is-invalid' : '' ?>" id="avatar" accept="image/*" />

            <img id="avatar-preview" src="" alt="Avatar Preview" class="img-fluid rounded-circle shadow-sm" 
            style="max-width: 150px; max-height: 150px; margin-top: 10px; display: none; object-fit: cover;" />

            <?php if (isset($errors['avatar'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['avatar'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Submit -->
          <button type="submit" name="submit" class="btn btn-primary">Add Contact</button>
        </form>

      </div>
    </div>

  </div>

  <?php include_once __DIR__ . '/../src/partials/footer.php' ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('avatar');
        const previewImage = document.getElementById('avatar-preview');
        const defaultAvatarSrc = previewImage.src;

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();

                if (!file.type.startsWith('image/')) {
                    previewImage.style.display = 'none';
                    return; 
                }

                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                };

                reader.readAsDataURL(file);
            } else {
                previewImage.src = defaultAvatarSrc;
                previewImage.style.display = 'block';
            }
        });
    });
</script>
</body>

</html>