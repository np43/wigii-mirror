A 2D Geometry addon which provides :
- Geometry2DFL : A func exp library to do 2D analytical geometry.
- GeometryCliFL : A func exp library to manipulate 2D geometrical objects from the command line.
- GeometryElementEvaluator: A ReportingEvaluator which executes 2D geometry func exps and draws graphs in the html 5 / canvas format.

In order to use the Geometry addon :

1. Activate the Geometry addon in the /Wigii/data/wigiiSystem/core/_webImplExecutor/autoload.php
	// addon: Geometry
	if (!$ok) {
		$filename = ADDONS_PATH."Geometry/$class_name.php";
		$ok = file_exists($filename);
	}

2. Add a reference to the GeometryException in the /Wigii/data/wigiiSystem/addons/errorcodes.xml
	<GeometryException from="10100" to="10199"/>

3. Declare the GeometryCliFL and Geometry2DFL in the FuncExpVM bootstrap module
Add the following line the in the config.php of your client :
	
	// Configures FuncExpVM modules
	ServiceProvider::configureClass('FuncExpVM', ObjectConfigurator::createInstance(array('setBootstrapModules' => array('GeometryCliFL', 'Geometry2DFL'))));
	
	
4. Activate the Computations wigii module. 
An example of configuration can be found in config-examples/Computations_config.xml
The config-examples/Geometry_Computations_Examples.csv file contains some examples of Computations that can be imported.
The config-examples/Geometry_Computations_Examples_html.zip contains the generated html graphs from the computations examples to give an idea of the result without running again the computations.





In order to store mathematical sequences as objects that can be manipulated in wigii using CLI :

1. Activate the Sequences wigii module.
An example of configuration can be found in config-examples/Sequences_config.xml
Be careful to also activate the sub-element config Values_config.xml

2. Link the sequence repository to the GeometryCliFL.
Add the following lines the in the config_cli.php of your client :

	// Configures FuncExpVM modules
	ServiceProvider::configureClass('FuncExpVM', ObjectConfigurator::createInstance(array('setBootstrapModules' => array('GeometryCliFL', 'Geometry2DFL'))));

	// Configures Sequence repository
	ServiceProvider::configureClass('GeometryCliFL', ObjectConfigurator::createInstance(array('setSequenceRepositoryId' => THE_GROUP_ID_WHERE_TO_STORE_SEQUENCES)));
