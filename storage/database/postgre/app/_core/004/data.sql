UPDATE modules SET pos = pos + 1 WHERE pos >= 1;
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('apilog', 1, 1, 1, '', '\modules\tcontrol\apilog\ApilogController', 1, 'database', 'red');