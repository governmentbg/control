INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Password', '{ "minLength" : 8, "doNotMatchUser" : true, "doNotContainUser" : true, "minStrength" : 3, "doNotUseBad" : 2500, "doNotUseSame" : true, "changeEvery" : "10 years", "changeFirst" : true, "allowPlainText" : false }', 1, 0, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Certificate', '{}', 2, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('CertificateAdvanced', '{ "ocsp" : true, "crl" : true, "selfsigned" : false, "roots" : { "identifier" : "-----BEGIN CERTIFICATE----..." } }', 3, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('LDAP', '{ "host" : "127.0.0.1", "base" : "DC=host,DC=ad", "user" : null, "pass" : null, "attr" : "name,mail,userPrincipalName,distinguishedName" } ', 4, 1, '{"ip":["10.0.0.0/16"]}');
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('SMTP', '{ "host" : "127.0.0.1" }', 5, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('StampIT', '{ "public" : "oVGQjtygtjdK067X5limM8Cs5w2yiub0", "private" : "7pdKy3cQp9I3MMCpeq7A49qFRIMfyLCg", "permissions" : "pid,name,mail,organization" }', 6, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('AzureAD', '{ "public" : "...", "private" : "...", "permissions" : null, "tenant" : "" }', 7, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Facebook', '{ "public" : "...", "private" : "...", "permissions" : null }', 8, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Github', '{ "public" : "...", "private" : "...", "permissions" : null }', 9, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Google', '{ "public" : "...", "private" : "...", "permissions" : null }', 10, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('LinkedIn', '{ "public" : "...", "private" : "...", "permissions" : null }', 11, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Microsoft', '{ "public" : "...", "private" : "...", "permissions" : null }', 12, 1, null);

INSERT INTO users (name, mail, tfa, disabled, avatar, avatar_data) VALUES ('Администратор', 'admin@local', 0, 0, null, null);

INSERT INTO permissions (perm, created) VALUES ('dashboard/errors', NOW());
INSERT INTO permissions (perm, created) VALUES ('errors', NOW());
INSERT INTO permissions (perm, created) VALUES ('groups', NOW());
INSERT INTO permissions (perm, created) VALUES ('log', NOW());
INSERT INTO permissions (perm, created) VALUES ('log/viewraw', NOW());
INSERT INTO permissions (perm, created) VALUES ('mail', NOW());
INSERT INTO permissions (perm, created) VALUES ('modules', NOW());
INSERT INTO permissions (perm, created) VALUES ('organization', NOW());
INSERT INTO permissions (perm, created) VALUES ('permissions', NOW());
INSERT INTO permissions (perm, created) VALUES ('settings', NOW());
INSERT INTO permissions (perm, created) VALUES ('settings/adminer', NOW());
INSERT INTO permissions (perm, created) VALUES ('settings/files', NOW());
INSERT INTO permissions (perm, created) VALUES ('settings/shell', NOW());
INSERT INTO permissions (perm, created) VALUES ('translation', NOW());
INSERT INTO permissions (perm, created) VALUES ('uploads', NOW());
INSERT INTO permissions (perm, created) VALUES ('users', NOW());
INSERT INTO permissions (perm, created) VALUES ('pending', NOW());
INSERT INTO permissions (perm, created) VALUES ('users/impersonate', NOW());
INSERT INTO permissions (perm, created) VALUES ('users/master', NOW());

INSERT INTO organization (lft, rgt, lvl, pid, pos, title) VALUES (1, 2, 0, NULL, 0, 'Корен');

INSERT INTO grps (name, created) VALUES ('Супер администратори', NOW());
INSERT INTO grps (name, created) VALUES ('Обикновени', NOW());

INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'dashboard/errors', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'errors', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'groups', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'log', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'log/viewraw', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'mail', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'organization', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'permissions', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'settings', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'settings/adminer', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'settings/files', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'settings/shell', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'translation', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'uploads', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'users', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'pending', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'modules', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'users/impersonate', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'users/master', NOW());

INSERT INTO user_groups (usr, grp, main, created) VALUES (1, 1, 1, NOW());
INSERT INTO user_groups (usr, grp, main, created) VALUES (1, 2, 0, NOW());

INSERT INTO user_organizations (usr, org) VALUES (1, 1);

INSERT INTO user_providers (provider, id, usr, name, data, created, used) VALUES ('PasswordDatabase', 'admin', 1, '', '$2y$10$98aIL6pV51r.HlzwIbJ7aeCUL9R8C0CmdtMeIc66VRGk8lA8O8k2.', NOW(), NULL);

INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('dashboard', 1, 0, 1, '', '\modules\common\dashboard\Dashboard', 0, 'home', 'purple');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('users', 1, 1, 1, 'administration', '\modules\administration\users\UsersController', 9, 'user', 'orange');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('pending', 1, 1, 1, 'administration', '\modules\administration\pending\PendingController', 10, 'user plus', 'teal');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('organization', 1, 1, 1, 'administration', '\modules\administration\organization\OrganizationController', 11, 'sitemap', 'yellow');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('groups', 1, 1, 1, 'administration', '\modules\administration\groups\GroupsController', 12, 'users', 'olive');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('permissions', 1, 1, 1, 'administration', '\modules\administration\permissions\PermissionsController', 13, 'lock', 'green');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('translation', 1, 1, 1, 'administration', '\modules\administration\translation\TranslationController', 14, 'language', 'purple');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('mail', 0, 1, 1, 'administration', '\modules\administration\mail\MailController', 15, 'mail', 'teal');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('maildb', 1, 1, 1, 'administration', '\modules\administration\maildb\MailDBController', 16, 'mail', 'teal');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('uploads', 1, 1, 1, 'administration', '\modules\administration\uploads\UploadsController', 17, 'upload', 'brown');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('log', 1, 1, 1, 'administration', '\modules\administration\log\LogController', 18, 'book', 'blue');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('errors', 1, 1, 1, 'administration', '\modules\administration\errors\ErrorsController', 19, 'bug', 'red');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('modules', 1, 1, 1, 'administration', '\modules\administration\modules\ModulesController', 20, 'puzzle', 'purple');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('settings', 1, 1, 1, 'administration', '\modules\administration\settings\SettingsController', 21, 'cogs', 'black');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('profile', 1, 0, 0, '', '\modules\common\profile\ProfileController', 24, 'user', 'orange');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('authentication', 0, 0, 0, 'administration', '\modules\administration\authentication\AuthenticationController', 25, 'sign in', 'red');
