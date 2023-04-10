UPDATE modules SET pos = pos + 1 WHERE pos >= 2;
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('siksmap', 1, 1, 1, '', '\modules\tcontrol\siksmap\SiksmapController', 2, 'globe', 'purple');
UPDATE modules SET pos = pos + 1 WHERE pos >= 3;
INSERT INTO modules (name, loaded, dashboard, menu, parent, classname, pos, icon, color) VALUES ('mikreport', 1, 1, 1, '', '\modules\tcontrol\mikreport\MikreportController', 3, 'table', 'red');