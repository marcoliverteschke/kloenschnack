local:
	rsync -aP server /Applications/MAMP/htdocs/kloenschnack/
	rsync -aP app/* /Applications/MAMP/htdocs/kloenschnack/

leopard:
	rsync -aP server kloenschnack@leopard.planwerk6.local:htdocs/
	rsync -aP app/* kloenschnack@leopard.planwerk6.local:htdocs/
