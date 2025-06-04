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

If you wish to opt out of this marketing email going forward, please email us at registration2023@Philcon.org or send us postal email at:.

Philcon #id#
PO BOX 8303
Philadelphia, PA 19101-8303

' where appName = 'controll' and appPage = 'emails' and appSection = 'marketing' and txtItem = 'text';

update controllTxtItems set contents = '<div>Hello!</div>
<div>&nbsp;</div>
<div>#label# is almost upon us!</div>
<div>&nbsp;</div>
<div>You are receiving this email because your email address is associated with a valid registration to attend last year''s convention, but we don''t have you registered for this year''s convention. You can always register on-site, but you can save money by purchasing your membership in advance at <a title="Registration Web Site" href="#server#">#server#</a>. The registration site will allow you to see the status of your current memberships and has the ability to group your family together by letting you "manage" their accounts.</div>
<div>&nbsp;</div>
<div>This year, we are at the same hotel, #hotelname#, at #hoteladdr#. Please register for rooms as soon as possible as the block will be closing soon. You can find a link to the hotel registration site on our website at <a href="#hotelwebsite#">#hotelwebsite#</a>.</div>
<div>&nbsp;</div>
<div>Our programming team is putting together a great schedule for us this year, and you will be able to soon take a look at it at <a href="#schedulepage#">#schedulepage#</a>.</div>
<div>&nbsp;</div>
<div>Information about other activities, as well as our Guests of Honor, can be found on our website at <a title="Convention Website" href="#website#">#website#</a>.</div>
<div>&nbsp;</div>
<div>The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events. For specific information, including our full Anti-Harassment Policy, see <a title="Policy" href="#policy#">#policy#</a>.</div>
<div>&nbsp;</div>
<div>If you have any further questions, please feel free to contact us at <a title="Feedback Email Address" href="emailto:#feedbackemail#">#feedbackemail#</a>, or visit our website for information on how to contact individual departments.</div>
<div>&nbsp;</div>
<div>We hope to see you at the convention!</div>
<div>&nbsp;</div>
<div>Philcon Registration Team</div>
<div>&nbsp;</div>
<div>If you wish to opt out of this marketing email going forward, please email us at registration2023@Philcon.org or send us postal email at:.</div>
<div>&nbsp;</div>
<div>Philcon #id#</div>
<div>PO BOX 8303</div>
<div>Philadelphia, PA 19101-8303</div>
<p>&nbsp;</p>' where appName = 'controll' and appPage = 'emails' and appSection = 'marketing' and txtItem = 'html';

INSERT INTO patchLog(id, name) VALUES(xx, 'Marketing Customization');

