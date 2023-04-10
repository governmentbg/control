UPDATE modules SET pos = pos + 3 WHERE pos >= 4;
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('devices', 1, 1, 1, '', '\modules\tcontrol\devices\DevicesController', 4, 'phone volume', 'orange');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('servers', 1, 1, 1, '', '\modules\tcontrol\servers\ServersController', 5, 'server', 'yellow');
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('restreamers', 1, 1, 1, '', '\modules\tcontrol\restreamers\RestreamersController', 6, 'broadcast tower', 'red');
