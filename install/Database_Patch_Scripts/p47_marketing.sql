/*
 * Changes to re-enable marketing emails from reg-admin
 */

INSERT INTO controllAppPages(appName,appPage,pageDescription) VALUES
('controll', 'emails', 'customizable emails from the controll app');

INSERT INTO controllAppSections (appName, appPage, appSection, sectionDescription) VALUES
('controll', 'emails', 'marketing', 'Marketing Email - Not bought this year, bought last year'),
('controll', 'emails', 'comeback', 'Comeback Email - Not bought insert into a few years'),
('controll', 'emails', 'reminder', 'Reminder Email - Reminder to attend - has membership');

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
('controll', 'emails','marketing','text','Custom Text for the plain text marketing email - not registered this year'),
('controll', 'emails','marketing','html','Custom Text for the html marketing email - not registered this year'),
('controll', 'emails','comeback','text','Custom Text for the plain text comeback email - not registered insert into past few years'),
('controll', 'emails','comeback','html','Custom Text for the html comeback email - not registered insert into past few years'),
('controll', 'emails','reminder','text','Custom Text for the plain text attendence reminder email'),
('controll', 'emails','reminder','html','Custom Text for the html attendence reminder email');

/*
 * default emails for the distribution
 */
update controllTxtItems set contents = '<p><strong>Hello!</strong></p>
<p>#label# is almost upon us!</p>
<p>You are receiving this email because your email address is associated with a valid registration to attend last year&rsquo;s convention,
but we don&rsquo;t have you registered for this year&rsquo;s convention. You can always register on-site, but you can save money by purchasing
your membership in advance at #server#. The registration site will allow you to see the status of your current memberships and has the ability to
group your family together by letting you "manage" their accounts.</p>
<p>This year, we are at the same hotel, #hotelname#, at #hoteladdr#. Please register for rooms as soon as possible as the block will be closing soon.
You can find a link to the hotel registration site on our website at
<a title="Hotel Information Web Page" href="#hotelwebsite#">#hotelwebsite#</a>.</p>
<p>Our programming team is putting together a great schedule for us this year, and you will be able to soon take a look at it at
<a title="ComCl&aacute;r Schedule Page" href="#schedulepage#">#schedulepage#</a>.</p>
<p>Information about other activities, as well as our Guests of Honor, can be found on our website at
<a title="#label# Web Site" href="#website#">#website#</a>.</p>
<p>The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and
other #orgabv#-sponsored events. For specific information, including our full Anti-Harassment Policy, see
<a title="#label# Policy" href="#policy#">#policy#</a>.</p>
<p>If you have any further questions, please feel free to contact us at <a href="mailto:#feedbackemail#">#feedbackemail#</a>,
or visit our website for information on how to contact individual departments.</p>
<p>We hope to see you at the convention!</p>
<p>Philcon Registration Team</p>
<p>If you wish to opt out of this marketing email going forward, please email us at <a href="mailto:#regadminemail#">#regadminemail#</a> or send us postal email at:</p>
<p>#label#<br>PO BOX 8303<br>Philadelphia, PA 19101-8303</p>'
where appName = 'controll' and appPage = 'emails' and appSection = 'marketing' and txtItem = 'html';

update controllTxtItems set contents = '<p>You are receiving this email because your email address is associated with a valid registration to attend this
year&rsquo;s convention. To check the status of your, or the rest of your family&rsquo;s, registration you can always visit the registration portal at #server#.</p>
<p>This year we are at the same hotel, which is now the #hotelname#, at #hoteladdr#. Badges can be picked up or purchased at #conname# Registration, which is #pickupareatext#. #addlpickuptext#</p>
<p>Our programming team has put together a great schedule for us this year, and you can take a look at it at #schedulepage# on your computer or portable device. Information about other activities, as well as our Guests of Honor, can be found on our website at #website#.</p>
<p>The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events. For specific information, including our full Anti-Harassment Policy, see #policy#.</p>
<p>If you have any further questions, please feel free to contact us at #feedbackemail#, or visit our website for information on how to contact individual departments.</p>
<p><strong>See you at the convention!</strong></p>
<p><strong>Additional Information:</strong></p>
<ul>
<li><strong><span class="s1">Where do I get my badge?</span></strong>
<ul>
<li><span class="s1">If you come in the front door of the hotel, continue past the hotel registration desk to the pre-function space.<span class="Apple-converted-space">&nbsp; </span>Registration is just through the entrance to the pre-function space on the right.</span></li>
<li><span class="s1">If you come in the side door from the parking lot, registration is rightget in front of you in the pre-function space.</span></li>
</ul>
</li>
</ul>
<p style="padding-left: 40px;"><span class="s1">Get in line for Check-In, and have your ID ready.</span></p>
<ul>
<li><strong><span class="s1">Registration hours are</span></strong></li>
<li style="list-style-type: none;">
<ul>
<li class="p1"><span class="s1">Friday: </span><span class="s1">3pm &ndash; 8pm</span></li>
<li class="p1"><span class="s1">Saturday: </span><span class="s1">9am &ndash; 7pm</span></li>
<li class="p1"><span class="s1">Sunday: 9</span><span class="s1">am &ndash; 1pm</span></li>
</ul>
</li>
</ul>
<ul>
<li class="p1"><strong><span class="s1">Do I have to show ID?</span></strong>
<ul>
<li class="p1"><span class="s1">Yes. If you pre-purchased a badge, we would like to make sure tat no one else picks up your badge. We are very reasonable about what we consider valid ID; we just need to know the badge is going to the right person.</span></li>
</ul>
</li>
<li class="p1"><span class="s1"><strong>Remember a mask strongly recommended at Philcon.<br><br></strong></span></li>
<li class="p1"><strong><span class="s1">Can I pick up my spouse/girlfriend/child&rsquo;s membership as well as my own?</span></strong>
<ul>
<li class="p1"><span class="s1">Maybe! You can pick up someone else&rsquo;s membership&nbsp;<strong>IF</strong> one of the following is true:</span>
<ul>
<li class="p1"><span class="s1">The two memberships share a last name.</span></li>
<li class="p1"><span class="s1">The two memberships share an address.</span></li>
<li class="p1"><span class="s1">The two memberships were purchased on the same transaction.</span></li>
</ul>
</li>
<li class="p1"><span class="s1">You must have ID for both memberships.</span></li>
<li class="p1"><span class="s1">If you are picking up a membership for someone else, we will also request that you leave us with a cell phone number where we can reach you at con, in case they come looking for their badge at Registration before they find you.</span></li>
</ul>
</li>
</ul>
<ul>
<li class="p1"><strong><span class="s1">I changed my mind about my badge name! I just came up with the coolest thing, but I put something on my form that I hate now!</span></strong>
<ul>
<li class="p1"><span class="s1">That&rsquo;s okay! When you come to pick up your badge, just let us know you&rsquo;d like to change your badge name before we print it out, and we can make the change at the door.</span></li>
</ul>
</li>
</ul>
<ul>
<li class="p1"><strong><span class="s1">We don&rsquo;t need no stinkin&rsquo; badges!</span></strong>
<ul>
<li class="p1"><span class="s1">Our badges don&rsquo;t stink, and yes, you do need one.&nbsp;</span></li>
<li class="p1"><span class="s1">All #conname# members are required to be have a badge at all times in #conname# spaces. </span></li>
<li class="p1"><span class="s1">The badge should be worn so as to be clearly visible and must be presented to any #conname# volunteer checking badges on behalf of the convention.</span></li>
<li class="p1"><span class="s1">We encourage members to wear their badges above the waist because a higher number of badges are lost when worn at hip level.</span></li>
<li class="p1"><span class="s1">If you do lose your badge, you can check at Registration or the Lost and Found at Ops to see if it has been turned in</span></li>
<li class="p1"><span class="s1">Most conventions require you to purchase a new membership if your original badge is lost and has not been found, and that is an option. Such purchases are not refundable if the badge is later located.</span></li>
<li class="p1"><span class="s1">Alternatively, if the registration lead can confirm that someone has purchased or otherwise been granted a currently valid membership to #conname, they may, at their discretion, issue a replacement badge.<span class="Apple-converted-space">&nbsp; </span>A donation of at least $20 to #org# is requested for that service.</span></li>
</ul>
</li>
</ul>
<ul>
<li class="p1"><strong><span class="s1">What about Participants, Dealers, and Artists?</span></strong>
<ul>
<li class="p1"><span class="s1"">You should be receiving soon or have already received a separate email from your respective departments with further information.</span></li>
<li class="p1"><span class="s1">All badges, independent of your role at #conname#, are picked up at Registration!</span></li>
</ul>
</li>
</ul>
<p class="p1"><span class="s1">You are receiving this email because your email address is associated with a valid membership for #label#. You will receive a post-con survey, and then no further emails from us, unless we need to contact you individually. If you wish to opt out of the survey email, please email us at <a href="mailto:#regadminemail#">#</a></span><a href="mailto:#regadminemail#"><span class="s1">regadminemail#.</span></a></p>
<p class="p1">#label#<br><span class="s1">PO BOX 8303<br></span><span class="s1">Philadelphia, PA 19101-8303</span></p>'
where appName = 'controll' and appPage = 'emails' and appSection = 'reminder' and txtItem = 'html';

update controllTxtItems set contents = 'Hello!

#label# is almost upon us!

You are receiving this email because your email address is associated with a valid registration to attend last year''s convention, but we don''t have you registered for this year''s convention. You can always register on-site, but you can save money by purchasing your membership in advance at #server#. The registration site will allow you to see the status of your current memberships and has the ability to group your family together by letting you "manage" their accounts.

This year, we are at the same hotel, #hotelname#, at #hoteladdr#. Please register for rooms as soon as possible as the block will be closing soon. You can find a link to the hotel registration site on our website at #hotelwebsite#.

Our programming team is putting together a great schedule for us this year, and you will be able to soon take a look at it at #schedulepage#.

Information about other activities, as well as our Guests of Honor, can be found on our website at #website#.

The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events. For specific information, including our full Anti-Harassment Policy, see #policy#.

If you have any further questions, please feel free to contact us at #feedbackemail#, or visit our website for information on how to contact individual departments.

We hope to see you at the convention!

Philcon Registration Team

If you wish to opt out of this marketing email going forward, please email us at #regadminemail# or send us postal email at:.

#label#
PO BOX 8303
Philadelphia, PA 19101-8303
'
where appName = 'controll' and appPage = 'emails' and appSection = 'marketing' and txtItem = 'text';

update controllTxtItems set contents = '#label# is almost upon us!

You are receiving this email because your email address is associated with a valid registration to attend this year''s convention. To check the status of your, or the rest of your family''s, registration you can always visit the registration portal at #server#.

This year we are at the same hotel, which is now the #hotelname#, at #hoteladdr#. Badges can be picked up or purchased at #conname# Registration, which is #pickupareatext#. #addlpickuptext#

Our programming team has put together a great schedule for us this year, and you can take a look at it at #schedulepage# on your computer or portable device. Information about other activities, as well as our Guests of Honor, can be found on our website at #website#.

The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events. For specific information, including our full Anti-Harassment Policy, see #policy#.

If you have any further questions, please feel free to contact us at #feedbackemail#, or visit our website for information on how to contact individual departments.


See you at the convention!

Additional Information:

Where do I get my badge?
  * If you come in the front door of the hotel, continue past the hotel registration desk to the pre-function space. Registration is just through the entrance to the pre-function space on the right.
  * If you come in the side door from the parking lot, registration is right in front of you in the pre-function space.

Get in line for Check-In, and have your ID ready.

Registration hours are
  * Friday: 3pm - 8pm
  * Saturday: 9am - 7pm
  * Sunday: 9am - 1pm

Do I have to show ID?
  * Yes. If you pre-purchased a badge, we would like to make sure tat no one else picks up your badge. We are very reasonable about what we consider valid ID; we just need to know the badge is going to the right person.

Remember a mask strongly recommended at Philcon.

Can I pick up my spouse/girlfriend/child’s membership as well as my own?
  * Maybe! You can pick up someone else’s membership IF one of the following is true:
  * The two memberships share a last name.
  * The two memberships share an address.
  * The two memberships were purchased on the same transaction.
  * You must have ID for both memberships.
  * If you are picking up a membership for someone else, we will also request that you leave us with a cell phone number where we can reach you at con, in
case they come looking for their badge at Registration before they find you.

I changed my mind about my badge name! I just came up with the coolest thing, but I put something on my form that I hate now!
  * That’s okay! When you come to pick up your badge, just let us know you’d like to change your badge name before we print it out, and we can make the
change at the door.

We don’t need no stinkin’ badges!
  * Our badges don’t stink, and yes, you do need one.
  * All #conname# members are required to be have a badge at all times in #conname# spaces.
  * The badge should be worn so as to be clearly visible and must be presented to any #conname# volunteer checking badges on behalf of the convention.
  * We encourage members to wear their badges above the waist because a higher number of badges are lost when worn at hip level.
  * If you do lose your badge, you can check at Registration or the Lost and Found at Ops to see if it has been turned in
  * Most conventions require you to purchase a new membership if your original badge is lost and has not been found, and that is an option. Such purchases
are not refundable if the badge is later located.
  * Alternatively, if the registration lead can confirm that someone has purchased or otherwise been granted a currently valid membership to #conname, they
may, at their discretion, issue a replacement badge. A donation of at least $20 to #org# is requested for that service.

What about Participants, Dealers, and Artists?
  * You should be receiving soon or have already received a separate email from your respective departments with further information.
  * All badges, independent of your role at #conname#, are picked up at Registration!

You are receiving this email because your email address is associated with a valid membership for #label#. You will receive a post-con survey, and then no further emails from us, unless we need to contact you individually. If you wish to opt out of the survey email, please email us at #regadminemail#.

#label#
PO BOX 8303
Philadelphia, PA 19101-8303
' where appName = 'controll' and appPage = 'emails' and appSection = 'reminder' and txtItem = 'text';

INSERT INTO patchLog(id, name) VALUES(xx, 'Marketing Customization');

