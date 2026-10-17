<?php if ($page==='membership' && !current_user()): ?><div class="card card-body"><h3>Join our community</h3><p>Create an account or sign in to submit and track your membership application.</p><a class="btn" href="/register">Create account</a> <a class="text-link" href="/login">Sign in</a></div><?php else: ?>
<form method="post">
<?= csrf() ?><input type="hidden" name="action" value="<?= $page==='membership'?'membership':'enquiry' ?>">
<h3><?= e($formTitle) ?></h3><p>Your details will be received securely by the Foundation’s administration team.</p>
<div class="fields"><label>Full name<input name="name" autocomplete="name" minlength="2" maxlength="120" required></label><label>Email address<input type="email" name="email" autocomplete="email" maxlength="254" required></label></div>
<label>Mobile number<input type="tel" name="mobile" autocomplete="tel" maxlength="30"></label>
<?php if ($page === 'membership'): ?><label>Membership interest<select name="interest"><option>Individual membership</option><option>Supporting membership</option></select></label><label>Address<textarea name="address" autocomplete="street-address" maxlength="1000"></textarea></label><?php endif ?>
<?php if ($page === 'get-involved'): ?><label>I am interested in<select name="interest"><option>Volunteering</option><option>Internship</option><option>Career opportunities</option></select></label><?php endif ?>
<?php if ($page === 'partnerships'): ?><label>Organisation<input name="organisation" maxlength="160"></label><label>Partnership type<select name="interest"><option>CSR partnership</option><option>Institutional partnership</option><option>Programme sponsorship</option></select></label><?php endif ?>
<label><?= $page === 'membership' ? 'Why would you like to join?' : 'Your message' ?><textarea name="message" maxlength="3000" required></textarea></label>
<label class="check"><input type="checkbox" name="consent" value="1" required><span>I consent to the Foundation processing this application or enquiry as described in the privacy notice.</span></label><button class="btn" type="submit">Submit →</button></form>

<?php endif ?>