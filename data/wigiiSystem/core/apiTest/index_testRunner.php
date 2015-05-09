<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * test index for TestRunner
 * Created by CWE on 18 juin 09
 */
TestRunner::start();
TestRunner::changeTestDataSet(new WigiiApiTestConfig());
//include(CORE_PATH."apiTest/testServiceProvider.php");
//include(CORE_PATH."apiTest/testWigiiApiTestConfig.php");
//include(CORE_PATH."apiTest/rebuildGroupsGroups.php");
//include(CORE_PATH."apiTest/cleanupAllLocks.php");
//include(CORE_PATH."apiTest/setupTestDataSet.php");
/***************API testing **************************************/
//include(CORE_PATH."apiTest/testUserAdminService.php");
//include(CORE_PATH."apiTest/testGroupAdminService.php");
//include(CORE_PATH."apiTest/testMatrix.php");
//include(CORE_PATH."apiTest/testStringTokenizer.php");
//include(CORE_PATH."apiTest/testFieldSelectorLogExpParser.php");
include(CORE_PATH."apiTest/testFieldSelectorFuncExpParser.php");
//include(CORE_PATH."apiTest/testElementServiceImpl.php");
//include(CORE_PATH."apiTest/testPRightsOnGroupORUser.php");
/***************end of API testing *******************************/
//include(CORE_PATH."apiTest/cleanupTestDataSet.php");
/***************Manual testing **************************************/
//include(CORE_PATH."apiTest/setupTestCasesModule.php");
//include(CORE_PATH."apiTest/cleanupTestCasesModule.php");
//include(CORE_PATH."apiTest/setupEventsModule.php");
//include(CORE_PATH."apiTest/cleanupEventsModule.php");
//include(CORE_PATH."apiTest/testFieldSelectorInInsertElement.php");
//include(CORE_PATH."apiTest/testWplToolbox.php");
//include(CORE_PATH."apiTest/testGuzzle.php");
//include(CORE_PATH."apiTest/testZipArchive.php");
/***************end of Manual testing *******************************/

//include(CORE_PATH."apiTest/testConfigServiceImpl.php");
//include(CORE_PATH."apiTest/testArrayMovingWindowIterator.php");
//include(CORE_PATH."apiTest/testStreamMovingWindowIterator.php");
//include(CORE_PATH."apiTest/testArrayIterator.php");
//include(CORE_PATH."apiTest/testGD.php");
//include(CORE_PATH."apiTest/testStdClass.php");
//include(CORE_PATH."apiTest/testFuncExpBuilder.php");
//include(CORE_PATH."apiTest/test_web_session.php");
//include(CORE_PATH."apiTest/testFormFacade.php");
//include(CORE_PATH."apiTest/testMySqlFacade.php");
//include(CORE_PATH."apiTest/testSessionAdminService.php");
//include(CORE_PATH."apiTest/testAuthenticationServiceWebImpl.php");
//include(CORE_PATH."apiTest/testTranslationService.php");
//include(CORE_PATH."apiTest/testExecutionService.php");
//include(CORE_PATH."apiTest/test_web_AsynchronusJs.php");
