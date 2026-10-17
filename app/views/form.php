<form method="post">
<input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
<h3><?= e($formTitle) ?></h3><p>Demo form: validates your details without storing or sending personal information.</p>
<div class="fields"><label>Full name<input name="name" autocomplete="name" minlength="2" maxlength="120" required></label><label>Email address<input type="email" name="email" autocomplete="email" maxlength="254" required></label></div>
<label>Mobile number<input type="tel" name="mobile" autocomplete="tel" maxlength="30"></label>
<?php if ($page === 'membership'): ?><label>Membership interest<select name="interest"><option>Individual membership</option><option>Supporting membership</option></select></label><label>Address<textarea name="address" autocomplete="street-address" maxlength="1000"></textarea></label><?php endif ?>
<?php if ($page === 'get-involved'): ?><label>I am interested in<select name="interest"><option>Volunteering</option><option>Internship</option><option>Career opportunities</option></select></label><?php endif ?>
<?php if ($page === 'partnerships'): ?><label>Organisation<input name="organisation" maxlength="160"></label><label>Partnership type<select name="interest"><option>CSR partnership</option><option>Institutional partnership</option><option>Programme sponsorship</option></select></label><?php endif ?>
<label><?= $page === 'membership' ? 'Why would you like to join?' : 'Your message' ?><textarea name="message" maxlength="3000" required></textarea></label>
<label class="check"><input type="checkbox" name="consent" value="1" required><span>I understand this is a demo and my application or enquiry will not be submitted.</span></label><button class="btn" type="submit">Validate demo form ↗</button></form>
