/*
 * P58 - art show additions, PHP tabulator reports, additional email custom text conversions,
 *
 */

/* comeback email custom text */
update controllTxtItems set contents = 'Hello [[FirstName]] [[LastName]],

#label# is almost upon us! You are receiving this email because your email address is associated with a valid registration to a prior convention, but you haven''t registered in the past few years and we don''t have you registered for this year''s convention.

We would like to encourage you to come back this year by letting you know that our early discount period is ending soon. You can see the complete
registration price list on our website at: #regpage#. You can register on-site of course, but if you register now you can save up to 20% on each membership.

This year, we are again at the #hotelname# at #hoteladdr#. Please register for rooms as soon as possible as the block will be closing soon.

Our programming team is putting together a great schedule for us this year, and you will soon be able to take a look at it at #schedulepage#. Information about other activities, as well as our Guests of Honor, can be found on our website at #website#.

The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events. For specific information, including our full Anti-Harassment Policy, #policy#.

If you have any further questions, please feel free to contact us at #feedbackemail#. or visit our website for information on how to contact individual departments.'
where appName = 'controll' and appPage = 'emails' and appSection = 'comeback' and txtItem = 'text';

update controllTxtItems set contents = '<p>Hello [[FirstName]] [[LastName]],</p>
<p>#label# is almost upon us! You are receiving this email because your email address is associated with a valid registration to a prior convention, but you haven''t registered in the past few years and we don''t have you registered for this year''s convention.</p>
<p>We would like to encourage you to come back this year by letting you know that our early discount period is ending soon. You can see the complete registration price list on our website at: <a href="#regpage#" target="_blank" rel="noopener">#regpage#</a>. You can register on-site of course, but if you
register now you can save up to 20% on each membership.</p>
<p>This year, we are again at the <a href="#hotelpage#" target="_blank" rel="noopener">#hotelname#</a>, at #hoteladdr#.  Please register for rooms as soon as possible as the block will be closing soon.</p>
<p>Our programming team is putting together a great schedule for us this year, and you will soon be able to take a look at it at <a href="#schedulepage#" target="_blank" rel="noopener">#schedulepage#</a>. Information about other activities, as well as our Guests of Honor, can be found on our website at <a href="#website#" target="_blank" rel="noopener">#website#</a>.</p>
<p>The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events.
For specific information, including our full Anti-Harassment Policy, see <a href="#policy#" target="_blank" rel="noopener">#policy#</a>.</p>
<p>If you have any further questions, please feel free to contact us at <a href="maito:#feedbackemail#" target="_blank" rel="noopener">#feedbackemail#</a>, or visit our website for information on how to contact individual departments.</p>'
where appName = 'controll' and appPage = 'emails' and appSection = 'comeback' and txtItem = 'html';

INSERT INTO patchLog(id, name) VALUES(p58, 'Release 2.2 Artshow and other changes');
