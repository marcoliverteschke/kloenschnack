local:
	rsync -aP htdocs/ /Applications/MAMP/htdocs/kloenschnack

leopard:
	rsync -aP htdocs/ kloenschnack@leopard.planwerk6.local:htdocs
