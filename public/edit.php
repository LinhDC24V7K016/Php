<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/classes/Contact.php';

use CT275\Labs\Contact;

include_once __DIR__ . '/../src/partials/header.php';

$contact = new Contact($PDO);

$id = isset($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_VALIDATE_INT): false;

if (!$id || !($contact->find($id))) {
   redirect('/');
}

$errors = [];
$old_avatar_path = $contact->avatar;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $contactData = [
      'name' => $_POST['name'] ?? '',
      'phone' => $_POST['phone'] ?? '',
      'notes' => $_POST['notes'] ?? '',
   ];

   $errors = $contact->validate($contactData);
   $target_dir = '/uploads/';
   $root_path = __DIR__ . '/..';

   $new_avatar_path = $contact->upload_avatar('avatar', $target_dir, $errors, $root_path);

   if (empty($errors)) {
      $contact->fill($contactData);
 

      if (!empty($new_avatar_path)) {
            $contact->avatar = $new_avatar_path;
        } else {
            $contact->avatar = $old_avatar_path;
        }

      $contact->save() && redirect('/');
   }

   if (!empty($old_avatar_path) && empty($new_avatar_path)) {
        $contact->avatar = $old_avatar_path;
    }
}
?>

<body>
  <?php include_once __DIR__ . '/../src/partials/navbar.php' ?>

  <!-- Main Page Content -->
  <div class="container">

    <?php
    $subtitle = 'Update your contacts here.';
    include_once __DIR__ . '/../src/partials/heading.php';
    ?>

    <div class="row">
      <div class="col-12 row justify-content-center">

        <div class="col-md-3 mt-3">     
            <?php
               $avatar_url = isset($contact) && $contact->avatar ? html_escape($contact->avatar) : '';
               $display_style = $avatar_url ? 'block' : 'none';
            ?>
            <img id="avatar-preview" 
                  src="<?= $avatar_url ?>" 
                  alt="Avatar Preview" class="img-fluid rounded-circle shadow-sm"
                  style="width: 300px; height: 300px; object-fit: cover; margin: 0 auto; display: <?= $display_style ?>;" 
            />
        </div>

        <form method="post" class="col-md-6" enctype="multipart/form-data">

          <input type="hidden" name="id" value="<?= $contact->id ?>">

          <!-- Name -->
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" maxlen="255" id="name" placeholder="Enter Name" value="<?= html_escape($contact->name) ?>" />

            <?php if (isset($errors['name'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['name'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Phone -->
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>" maxlen="255" id="phone" placeholder="Enter Phone" value="<?= html_escape($contact->phone) ?>" />

            <?php if (isset($errors['phone'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['phone'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label for="notes" class="form-label">Notes </label>
            <textarea name="notes" id="notes" class="form-control<?= isset($errors['notes']) ? ' is-invalid' : '' ?>" placeholder="Enter notes (maximum character limit: 255)"><?= html_escape($contact->notes) ?></textarea>

            <?php if (isset($errors['notes'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['notes'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Avatar -->
          <div class="mb-3">
            <label for="avatar" class="form-label">Avatar </label>
            
            <input type="file" name="avatar" class="form-control<?= isset($errors['avatar']) ? ' is-invalid' : '' ?>" 
                  id="avatar" accept="image/*" />
            
            <?php if (isset($errors['avatar'])) : ?>
               <span class="invalid-feedback">
                     <strong><?= $errors['avatar'] ?></strong>
               </span>
            <?php endif ?>
         </div>

          <!-- Submit -->
          <button type="submit" name="submit" class="btn btn-primary">Update Contact</button>
        </form>

      </div>
    </div>

  </div>

  <?php include_once __DIR__ . '/../src/partials/footer.php' ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('avatar');
        const previewImage = document.getElementById('avatar-preview');
        
        const oldAvatarSrc = previewImage.src; 

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();

                if (!file.type.startsWith('image/')) {
                    previewImage.src = oldAvatarSrc;
                    previewImage.style.display = oldAvatarSrc ? 'block' : 'none'; 
                    return; 
                }

                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                };

                reader.readAsDataURL(file);
            } else {
                previewImage.src = oldAvatarSrc;

                previewImage.style.display = oldAvatarSrc ? 'block' : 'none';
            }
        });
    });
  </script>
</body>

</html>