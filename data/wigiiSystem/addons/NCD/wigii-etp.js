/**
 *  This file is part of Wigii.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016-2017  Wigii.org
 *  @author     <http://www.wigii.org/system/libs>      Wigii.org 
 *  @link       <http://www.wigii-system.net>           <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>          GNU General Public License
 */

/**
 * Wigii NCD Language for ETPs : 
 * Expand, Translate, Program - Encode, Transmit, Power.
 * This language can be naturally used within the etp-start.html for your own creations.
 * Created by Wigii.org (camille@wigii.org), 11.02.2017
 * Modified by Wigii.org (camille@wigii.org and lionel@wigii.org), 10.05.2017
 * Updated version 2.0 by Camille Weber (camille@wigii.org), 27.10.2017
 */ 
(function (window, $, wigiiNcd){ 
	// Configuration options
	var wigiiNcdEtpOptions = undefined;
	if(window.wigiiNcdEtp && window.wigiiNcdEtp.options) wigiiNcdEtpOptions = window.wigiiNcdEtp.options;
	if(!wigiiNcdEtpOptions) wigiiNcdEtpOptions = {};
	// Private members management
	if(!wigiiNcdEtpOptions.privateNcdEtpMembers) wigiiNcdEtpOptions.privateNcdEtpMembers = {};
	/**
	 * Marks a member as private. A private member is not published into the wncd symbol.
	 *@param String memberName the name of the variable or function to be marked as private
	 */
	var ncdprivate = function(memberName) {
		wigiiNcdEtpOptions.privateNcdEtpMembers[memberName] = true;
	};


	// Wigii NCD ETP

	/**
	 * Language holder
	 */
	var wigiiNcdEtp = {};
	wigiiNcdEtp.instantiationTime = Date.now();ncdprivate('instantiationTime');
	wigiiNcdEtp.ctxKey = 'WigiiNcdEtp_'+wigiiNcdEtp.instantiationTime;ncdprivate('ctxKey');
	wigiiNcdEtp.version = function() {return "2.10";};
	
	// Execution environment

	if(!wigiiNcdEtpOptions.programOutput) wigiiNcdEtpOptions.programOutput = "#programOutput";
	
	/**
	 * Main HTML Emitter
	 */
	var html = wigiiNcd().getHtmlEmitter(wigiiNcdEtpOptions.programOutput);
	wigiiNcdEtp.html = html;ncdprivate('html');
	
	/**
	 * Main Program
	 */
	var programme = function(f) {
		html.clearErrors();
		try {f();html.end();}
		catch(exc) {html.publishException(exc);}
	};
	
	/**
	 * ETP Debugger
	 */
	wigiiNcdEtp.debug = {};
	wigiiNcdEtp.debug.initialize = function() {
		if($("#programWatcherContainer").length==0) {
			html.$().css('float','left').css('width','71%').after('<div id="programWatcherContainer"><div id="programWatcher"></div></div><div style="clear:both;"></div>');
		}
		$("#programWatcherContainer").show();
		return wigiiNcdEtp.debug;
	};
	wigiiNcdEtp.debug.$ = function() {return $("#programWatcherContainer");};
	
	/**
	 * Server logical proxy
	 */
	var serveur = {
		stockerDonnee : function(elementId, keyField, objet){
			if(objet===undefined) wigii().getRiseNcd().storeData(elementId, undefined, keyField);
			else wigii().getRiseNcd().storeData(elementId, keyField, objet);
		},
		obtenirDonnee : function(elementId){
			return wigii().getRiseNcd().getData(elementId);
		},
		creerEspaceStockage : function(groupId, description){
			return wigii().getRiseNcd().createDataStorage(groupId, description);
		},
		sysUsername : function() {
			return wigii().getRiseNcd().sysUsername();
		},
		rise : function() {
			return wigii().getRiseNcd();
		}
	};

	// NCD basic programming language modeler
	html.addModeler(function(html) {
		// Implementation
		html.impl.out = function(str,cssClass){html.startSpan(cssClass);html.put(str);html.endSpan();return html;}; 
		html.impl.h1 = function(str,cssClass){html.startH1(cssClass);html.put(str);html.endH1(); return html;}; 
		html.impl.p = function(cssClass) {html.startP(cssClass); return html;};
		html.impl.$p = function() {html.endP(); return html;};
		html.impl.b = function(str){html.startSpan('b');html.put(str);html.endSpan(); return html;};
		html.impl.i = function(str){html.startSpan('i');html.put(str);html.endSpan(); return html;}; 
		html.impl.u = function(str){html.startSpan('u');html.put(str);html.endSpan(); return html;}; 
		html.impl.a = function(url){html.put(wigiiNcd().getHtmlBuilder().putStartTag('a','href',url,'target','_blank','class',html.emittedClass()).put(url).putEndTag('a').html());return html;};
		html.impl.color = function(c,backgroundC,cssClass){html.startColor(c,backgroundC, cssClass);return html;};
		html.impl.$color = function(){html.endColor(); return html;};
		html.impl.bouton = function(label, onClick, cssClass, id, context){html.putButton(label, onClick, cssClass, id, context); return html;};
		html.impl.input = function(cssClass){return html.createTextArea(cssClass);};
		html.impl.display = function(backgroundC,textC,cssClass) {return html.createTextInput(cssClass).color(backgroundC,textC);};
		html.impl.insert = function(f) {
			html.htmlBuilder().insert(f).emit();
			return html;
		};
		// English translation
		html.impl.button = html.impl.bouton;
		
		// Exposes interface
		html.out = html.impl.out; 
		html.h1 = html.impl.h1; 
		html.p = html.impl.p;
		html.$p = html.impl.$p;
		html.b = html.impl.b;
		html.i = html.impl.i; 
		html.u = html.impl.u; 
		html.a = html.impl.a;
		html.color = html.impl.color;
		html.$color = html.impl.$color;
		html.bouton = html.impl.bouton;
		html.input = html.impl.input;
		html.display = html.impl.display;
		html.insert = html.impl.insert;
		// English symbols
		html.button = html.impl.button;
	});
	
	// HTML Grid modeler
	html.addModeler(function(html) {
		// Context
		html.context.gridInstance = undefined;
		// Implementation
		html.impl.grille = function(nLignes,nCols) {
			if(nLignes===undefined && nCols===undefined) return html.context.gridInstance;
			if(html.context.gridInstance) $("#"+html.context.gridInstance.ctxKey).remove();
			html.context.gridInstance = html.createGrid(nLignes,nCols);
			// Models grid instance
			html.context.gridInstance.cellule = html.impl.grille.cellule;
			html.context.gridInstance.couleur = html.impl.grille.couleur;
			html.context.gridInstance.texte = html.impl.grille.texte;
			html.context.gridInstance.onclick = html.impl.grille.onclick;
			html.context.gridInstance.nLignes = html.context.gridInstance.nRows;
			html.context.gridInstance.nColonnes = html.context.gridInstance.nCols;
			// Models a cell for ETP
			html.context.gridInstance.initializeCellForEtp = function(gridCell) {
				if(!gridCell.context.etpReady) {
					gridCell.texte = gridCell.text;
					gridCell.couleur = gridCell.color;
					gridCell.gauche = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.left(true));};
					gridCell.droite = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.right(true));};
					gridCell.bas = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.down(true));};
					gridCell.haut = function(){return html.context.gridInstance.initializeCellForEtp(gridCell.up(true));};
					gridCell.bouton = function(label,onClick) {$("#"+gridCell.id()).addClass('button');return gridCell.text(label).click(onClick);};
					gridCell.cellule = function(ligne,colonne) {return html.impl.grille.cellule(ligne,colonne);};
					var offsetImpl = gridCell.offset;
					gridCell.offset = function(ligne,colonne){return html.context.gridInstance.initializeCellForEtp(offsetImpl(ligne,colonne));};
					gridCell.espace = {};
					gridCell.context.etpReady = true;
				}
				return gridCell;
			}
			return html.context.gridInstance; 
		}; 
		html.impl.grille.cellule = function(ligne,colonne) {return html.context.gridInstance.initializeCellForEtp(html.context.gridInstance.cell(ligne-1,colonne-1));};
		html.impl.grille.couleur = function(ligne,colonne,couleur) {
			if(couleur===undefined) return html.context.gridInstance.cell(ligne-1,colonne-1).color();
			else {
				html.context.gridInstance.cell(ligne-1,colonne-1).color(couleur);
				return html.context.gridInstance;
			};
		}; 
		html.impl.grille.texte = function(ligne,colonne,texte) {
			if(texte===undefined) return html.context.gridInstance.cell(ligne-1,colonne-1).text();
			else {
				html.context.gridInstance.cell(ligne-1,colonne-1).text(texte);
				return html.context.gridInstance;
			}
		};
		html.impl.grille.onclick = function(f) {
			for(var gi = 0; gi < html.context.gridInstance.nRows(); gi++) {
				for(var gj = 0; gj < html.context.gridInstance.nCols(); gj++) {
					html.context.gridInstance.cell(gi,gj).click(function(cell){
						if(wigiiNcdEtp.programme.context) wigiiNcdEtp.programme.context.html(html);
						f(cell.x()+1,cell.y()+1);
					});
				}
			}
			return html.context.gridInstance;
		}; 	
		html.impl.grille.nLignes = function() {return html.context.gridInstance.nLignes();};
		html.impl.grille.nColonnes = function() {return html.context.gridInstance.nColonnes();};
		html.impl.grille.nRows = function() {return html.context.gridInstance.nRows();};
		html.impl.grille.nCols = function() {return html.context.gridInstance.nCols();};
		
		// Exposes interface
		html.grille = html.impl.grille;		
	});


	// Panier
	var panier = {
		impl:{ 
			collection: {},
			instance: function(index) {
				var self = this;
				self.index = index;
				self.list = [];
				// Méthodes
				self.lire = function(index) {
					if(index-1 < 0 || index-1 >= self.list.length) throw wigiiNcd().createServiceException("L'index "+index+" est invalide, il doit être compris entre 1 et "+self.list.length,wigiiNcd().errorCodes.INVALID_ARGUMENT);
					return self.list[index-1];
				};
				self.lirePremier = function() {return self.lire(1);};
				self.lireDernier = function() {return self.lire(self.taille());}
				self.remplacer = function(index,objet) {
					if(index-1 < 0 || index-1 >= self.list.length) throw wigiiNcd().createServiceException("L'index "+index+" est invalide, il doit être compris entre 1 et "+self.list.length,wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.list[index-1] = objet;
					return self;
				};
				self.ajouter = function(objet) { self.list.push(objet); return self;};
				self.sortirDernier = function() { return self.list.pop(); };
				self.taille = function() { return self.list.length; };
				self.montrer = function() {
					wigiiNcdEtp.debug.initialize().$().find("#programWatcher").html("<u>"+self.index+"</u> "+"Taille: "+self.taille()+"<br/>"+JSON.stringify(self.list));
					//wigii().log("Panier: "+self.index+" Taille: "+self.taille()+"\n"+JSON.stringify(self.list));
				};
				self.exporter = function() { return self.list; };
				self.importer = function(data) {
					if(data===undefined || data===null) return self;
					if($.isArray(data)) self.list = data;
					else self.list.push(data);
					return self;
				};
				self.vider = function() {
					self.list = [];
					return self;
				};
				self.trier = function(fonctionTri) {
					self.list.sort(fonctionTri);
					return self;
				};
				
				// English translation
				self.read = self.lire;
				self.readFirst = self.lirePremier;
				self.readLast = self.lireDernier;
				self.replace = self.remplacer;
				self.add = self.ajouter;
				self.removeLast = self.sortirDernier;
				self.size = self.taille;
				self.show = self.montrer;
				self.exportContent = self.exporter;
				self.importContent = self.importer;
				self.empty = self.vider;
				self.sort = self.trier;
			}
		},
		// Selecteur
		no: function(index) {
			var returnValue = panier.impl.collection['P_'+index];
			if(!returnValue) {
				returnValue = new (panier.impl.instance)(index);
				panier.impl.collection['P_'+index] = returnValue;
			}
			return returnValue;
		},
		reset: function() {panier.impl.collection = {};},
		// Méthodes
		lire : function(index) {return panier.no('default').lire(index);},
		lirePremier : function() {return panier.no('default').lirePremier();},
		lireDernier : function() {return panier.no('default').lireDernier();},
		remplacer : function(index,objet) {return panier.no('default').remplacer(index,objet);},
		ajouter : function(objet) {return panier.no('default').ajouter(objet);},
		sortirDernier : function() {return panier.no('default').sortirDernier();},
		taille : function() {return panier.no('default').taille();},
		montrer : function() {return panier.no('default').montrer();},
		exporter : function() {return panier.no('default').exporter();},
		importer : function(data) {return panier.no('default').importer(data);},
		vider : function() {return panier.no('default').vider();},
		trier : function(fonctionTri) {return panier.no('default').trier(fonctionTri);}
	};	
	
	
	// Formulaire
	var formulaire = {
		impl:{
			collection: {},
			instance: function(index) {
				var self = this;
				self.index = index;
				// Inner state
				self.context = {currentFieldName:undefined,fieldIndex:0};
				// Champ
				self.fields = {};		
				self.Field = function(formulaire, nom, label, couleurTexte, couleurFond, type, options) {
					var self = this;
					// Inner state
					self.context = {};
					self.context.formulaire = formulaire;
					self.context.nom = nom;
					self.context.id = 'formulaire_'+formulaire.index+'__'+nom;
					self.context.display = undefined;
					self.context.label = label;
					self.context.changed = false;
					
					// Propriétés
					self.id = function() {return self.context.id;};
					self.$ = function() {return $('#'+self.context.id);};
					self.nom = function() {return self.context.nom;};					
					self.input = function() {return self.context.display;};					
					
					// Méthodes
					self.valeur = function(v) {
						if(v===undefined) return self.context.display.text();
						else {
							self.context.display.text(v);
							self.context.changed=false;
							return self;
						}
					};					
					self.label = function(v) {
						if(v===undefined) return self.context.label;
						else {
							self.context.label = v;
							$('#'+self.context.id+' div.etp-label span:first-child').html(v);
							return self;
						}
					};
					self.label.couleur = function(couleurTexte) {
						if(couleurTexte===undefined) return $('#'+self.context.id+' div.etp-label span:first-child').css('color');
						else {
							$('#'+self.context.id+' div.etp-label span:first-child').css('color',couleurTexte);
							return self;
						}
					};
					self.couleur = function(couleurFond) {
						if(couleurFond===undefined) return $('#'+self.context.id).css('background-color');
						else {
							$('#'+self.context.id).css('background-color',couleurFond);
							return self;
						}				
					};
					self.vider = function() {
						self.context.display.text('');
						return self;
					};
					self.focus = function() {
						$('#'+self.context.display.ctxKey).focus();
						self.context.formulaire.context.currentFieldName = self.context.nom;
						return self;
					};
					self.disabled = function(bool) {
						if(bool===undefined) return $('#'+self.context.display.ctxKey).prop('disabled');
						else {
							$('#'+self.context.display.ctxKey).prop('disabled',bool);
							return self;
						}
					};
					self.readonly = function(bool) {
						if(bool===undefined) return $('#'+self.context.display.ctxKey).prop('readonly');
						else {
							$('#'+self.context.display.ctxKey).prop('readonly',bool);
							return self;
						}
					};
					self.changed = function(bool) {
						if(bool===undefined) return self.context.changed;
						else {
							self.context.changed = bool;
							return self;
						}
					};					
					// Chaînage
					self.champ = function(nom) {return self.context.formulaire.champ(nom);};
					
					// English translation
					self.name = self.nom;
					self.value = self.valeur;
					self.label.color = self.label.couleur;
					self.color = self.couleur;
					self.empty = self.vider;
					self.field = self.champ;					
					
					// HTML rendering
					wigiiNcdEtp.programme.currentDiv().putHtml(wigiiNcd().getHtmlBuilder()
						.putStartTag('div','id',self.context.id,'class','etp-field '+html.emittedClass())
						.putStartTag('div','class','etp-label '+html.emittedClass())
						.putStartTag('span','class',html.emittedClass())
						.put(self.context.label)
						.putEndTag('span')
						.putEndTag('div')
						.putStartTag('div','class','etp-value '+html.emittedClass())
						.putEndTag('div')
						.putEndTag('div')
						.html()
					);
					
					formulaire.context.fieldIndex++;/* increments field index to differentiate to fields with same timestamp */
					// Creates display according to type. Defaults to TextInput
					if(type) {
						switch(type) {
							case "TextArea":
								 self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createTextArea(undefined,formulaire.context.fieldIndex).color(couleurFond, couleurTexte);
								 break;
							case "PasswordInput":
								 self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createPasswordInput(undefined,formulaire.context.fieldIndex).color(couleurFond, couleurTexte);
								 break;
							case "Empty":
								self.context.display = undefined;
								break;
							default: throw wigiiNcd().createServiceException("Le type de champ '"+type+"' n'est pas supporté.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
						}
					}
					else self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createTextInput(undefined,formulaire.context.fieldIndex).color(couleurFond, couleurTexte);
					if(self.context.display) {
						self.context.display.id = function() {return self.context.display.ctxKey;};
						self.context.display.$ = function() {return $('#'+self.context.display.ctxKey);};
						self.context.display.couleur = self.context.display.color;
						
						// Behavior: 
						// - when typing, saves text
						// - on click, changes current form field to this one
						self.context.display.onInput(function(d,txt){d.context.text=txt;self.context.changed = true;});
						self.context.display.$().click(function(){self.context.formulaire.context.currentFieldName=self.context.nom;});
					}
					return self;
				}; 
				// Méthodes
				self.creerChamp = function(nom, label, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond);
					return self;
				};
				self.creerChampTexte = function(nom, label, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond,"TextArea");
					return self;
				};
				self.creerChampPassword = function(nom, label, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond,"PasswordInput");
					return self;
				};
				self.creerChampCustom = function(nom, label, valueRenderer, couleurTexte, couleurFond) {
					var field = self.fields[nom];
					if(field) throw wigiiNcd().createServiceException("Le champ "+nom+" existe déjà dans le formulaire, il ne peut pas être créé une deuxième fois. Choisissez un autre nom.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					// creates an empty field
					self.fields[nom] = new self.Field(self, nom, label, couleurTexte, couleurFond,"Empty");
					// extracts valueRenderer additional arguments
					var args;
					if(arguments.length > 5) args = Array.prototype.slice.call(arguments,5);
					else args = [];
					// runs the valueRenderer into the context of the field value div					
					var valueEmitter = self.fields[nom].$().find('div.etp-value').wncd('html');
					if($.isFunction(valueRenderer)) {
						var currentDiv = wncd.currentDiv();
						wncd.programme.context.html(valueEmitter);
						var valueHtml = valueRenderer.apply(null,args);
						if(valueHtml!==undefined) valueEmitter.putHtml(valueHtml);
						wncd.programme.context.html(currentDiv);
					}
					else valueEmitter.putHtml(valueRenderer);
					return self;
				};
				self.champ = function(nom) {
					var field = self.fields[nom];
					if(!field) throw wigiiNcd().createServiceException("Le champ "+nom+" n'existe pas dans le formulaire.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
					return field;
				};
				self.champExiste = function(nom) {
					var field = self.fields[nom];
					return (field?true:false);
				};
				self.champCourant = function(nom) {
					if(nom!==undefined) self.context.currentFieldName = nom;
					if(!self.context.currentFieldName) throw wigiiNcd().createServiceException("Aucun champ n'est actuellement sélectionné dans le formulaire. Cliquez sur un champ.",wigiiNcd().errorCodes.INVALID_STATE);
					return self.champ(self.context.currentFieldName);
				};
				self.vider = function() {
					for(var fieldName in self.fields) {
						self.fields[fieldName].vider();
					}
					return self;
				};
				self.supprimer = function() {formulaire.remove(self.index);};

				// English translation
				self.createField = self.creerChamp;
				self.createTextField = self.creerChampTexte;
				self.createPasswordField = self.creerChampPassword;
				self.createCustomField = self.creerChampCustom;
				self.field = self.champ;
				self.fieldExist = self.champExiste;
				self.currentField = self.champCourant;
				self.empty = self.vider;
				self.remove = self.supprimer;
				self.reset = function() {return formulaire.reset(self.index);};
			}
		},
		// Selecteur
		no: function(index) {
			var returnValue = formulaire.impl.collection['F_'+index];
			if(!returnValue) {
				returnValue = new (formulaire.impl.instance)(index);
				formulaire.impl.collection['F_'+index] = returnValue;
			}
			return returnValue;
		},
		remove: function(index) {
			var returnValue = formulaire.impl.collection['F_'+index];
			if(returnValue) {
				// removes from collection
				formulaire.impl.collection['F_'+index] = null;
				// deletes every field
				for(fieldName in returnValue.fields) {					
					var field = returnValue.fields[fieldName];
					$("#"+field.id()).remove();
					field.context.formulaire = undefined;
					field.context.display = undefined;
					field.context = undefined;
				}
			}
		},
		// Méthodes
		creerChamp : function(nom, label, couleurTexte, couleurFond) {return formulaire.no('default').creerChamp(nom, label, couleurTexte, couleurFond);},
		creerChampTexte : function(nom, label, couleurTexte, couleurFond) {return formulaire.no('default').creerChampTexte(nom, label, couleurTexte, couleurFond);},
		creerChampPassword : function(nom, label, couleurTexte, couleurFond) {return formulaire.no('default').creerChampPassword(nom, label, couleurTexte, couleurFond);},
		creerChampCustom : function(nom, label, valueRenderer, couleurTexte, couleurFond) {return formulaire.no('default').creerChampCustom(nom, label, valueRenderer, couleurTexte, couleurFond);},
		champ : function(nom) {return formulaire.no('default').champ(nom);},
		champExiste : function(nom) {return formulaire.no('default').champExiste(nom);},
		champCourant : function(nom) {return formulaire.no('default').champCourant(nom);},
		vider : function() {return formulaire.no('default').vider();},
		supprimer : function() {return formulaire.no('default').supprimer();},
		reset : function(index) {
			if(index===undefined) index = 'default';
			formulaire.no(index).supprimer();
			return formulaire.no(index);
		}
	};
	
	// Publish language
	wigiiNcdEtp.programme = programme;
	wigiiNcdEtp.out = html.out;
	wigiiNcdEtp.h1 = html.h1;
	wigiiNcdEtp.p = html.p;
	wigiiNcdEtp.$p = html.$p;
	wigiiNcdEtp.b = html.b;
	wigiiNcdEtp.i = html.i;
	wigiiNcdEtp.u = html.u;
	wigiiNcdEtp.a = html.a;
	wigiiNcdEtp.color = html.color; 
	wigiiNcdEtp.$color = html.$color;
	wigiiNcdEtp.bouton = html.bouton;
	wigiiNcdEtp.input = html.input;
	wigiiNcdEtp.display = html.display;
	wigiiNcdEtp.grille = html.grille;
	wigiiNcdEtp.panier = panier; 
	wigiiNcdEtp.formulaire = formulaire;
	wigiiNcdEtp.serveur = serveur; 
	wigiiNcdEtp.ctlSel = wigiiNcd().sel;
	
	// publishes data flow activities with dfa prefix
	for(var s in wigiiNcd().dfa) {
		wigiiNcdEtp['dfa'+s.substring(0,1).toUpperCase()+s.substring(1)] = wigiiNcd().dfa[s];
	};
	
	// English translation
	wigiiNcdEtp.program = wigiiNcdEtp.programme; 
	wigiiNcdEtp.button = wigiiNcdEtp.bouton; 
	wigiiNcdEtp.grid = wigiiNcdEtp.grille; 

	wigiiNcdEtp.basket = wigiiNcdEtp.panier;
	wigiiNcdEtp.basket.read = wigiiNcdEtp.panier.lire;
	wigiiNcdEtp.basket.readFirst = wigiiNcdEtp.panier.lirePremier;
	wigiiNcdEtp.basket.readLast =wigiiNcdEtp.panier.lireDernier;
	wigiiNcdEtp.basket.replace = wigiiNcdEtp.panier.remplacer;
	wigiiNcdEtp.basket.add = wigiiNcdEtp.panier.ajouter; 
	wigiiNcdEtp.basket.removeLast = wigiiNcdEtp.panier.sortirDernier;
	wigiiNcdEtp.basket.size = wigiiNcdEtp.panier.taille;
	wigiiNcdEtp.basket.show = wigiiNcdEtp.panier.montrer;
	wigiiNcdEtp.basket.exportContent = wigiiNcdEtp.panier.exporter;
	wigiiNcdEtp.basket.importContent = wigiiNcdEtp.panier.importer;
	wigiiNcdEtp.basket.empty = wigiiNcdEtp.panier.vider;
	wigiiNcdEtp.basket.sort = wigiiNcdEtp.panier.trier;
	
	wigiiNcdEtp.form = wigiiNcdEtp.formulaire;
	wigiiNcdEtp.form.createField = wigiiNcdEtp.formulaire.creerChamp;
	wigiiNcdEtp.form.createTextField = wigiiNcdEtp.formulaire.creerChampTexte;
	wigiiNcdEtp.form.createPasswordField = wigiiNcdEtp.formulaire.creerChampPassword;
	wigiiNcdEtp.form.createCustomField = wigiiNcdEtp.formulaire.creerChampCustom;
	wigiiNcdEtp.form.field = wigiiNcdEtp.formulaire.champ;
	wigiiNcdEtp.form.currentField = wigiiNcdEtp.formulaire.champCourant;
	wigiiNcdEtp.form.empty = wigiiNcdEtp.formulaire.vider;
	//remove is already defined. Do not translate again. wigiiNcdEtp.form.remove = wigiiNcdEtp.formulaire.supprimer;
	
	wigiiNcdEtp.server = wigiiNcdEtp.serveur;
	wigiiNcdEtp.server.getData = wigiiNcdEtp.serveur.obtenirDonnee;
	wigiiNcdEtp.server.storeData = wigiiNcdEtp.serveur.stockerDonnee;
	
	// Starting up and exporting symbols
	if(!window.wigiiNcdEtp || !window.wigiiNcdEtp.version || window.wigiiNcdEtp.version() < wigiiNcdEtp.version()) {
		window.wigiiNcdEtp = wigiiNcdEtp;
		
		// publish NCD ETP language symbols to wncd
		if(wigiiNcdEtpOptions.publishNcdEtpToWncd===undefined) wigiiNcdEtpOptions.publishNcdEtpToWncd = function(wigiiNcdEtp,wncd) {
			// publishes everything, except members considered as private			
			for(var member in wigiiNcdEtp) {
				if(!wigiiNcdEtpOptions.privateNcdEtpMembers[member]) wncd[member] = wigiiNcdEtp[member];
			}
			// creates shortcuts
			wncd.html = wigiiNcdEtp.html.clone;
		};
		if(!window.wncd) window.wncd = {};
		if(wigiiNcdEtpOptions.publishNcdEtpToWncd) wigiiNcdEtpOptions.publishNcdEtpToWncd(wigiiNcdEtp,window.wncd);
		
		// Publish symbols to window if flag is not explicitely set to false
		if(wigiiNcdEtpOptions.publishNcdEtpToWindow===undefined) wigiiNcdEtpOptions.publishNcdEtpToWindow = function(wigiiNcdEtp,window) {
			// publishes everything, except members considered as private			
			for(var member in wigiiNcdEtp) {
				if(!wigiiNcdEtpOptions.privateNcdEtpMembers[member]) window[member] = wigiiNcdEtp[member];
			}
		};
		if(wigiiNcdEtpOptions.publishNcdEtpToWindow) wigiiNcdEtpOptions.publishNcdEtpToWindow(wigiiNcdEtp,window);		
	}
	// Ready callback
	if(wigiiNcdEtpOptions.ncdEtpReady===undefined) wigiiNcdEtpOptions.ncdEtpReady = function(wigiiNcdEtp) {
		var footer = $("#footer");
		if(footer.length>0) footer.append('<span><i>&nbsp;Wigii NCD core v.'+wigiiNcdEtp.version()+' loaded</i></span>');
	}
	if(wigiiNcdEtpOptions.ncdEtpReady) wigiiNcdEtpOptions.ncdEtpReady(wigiiNcdEtp);
	// keeps options for Fx layer loading 
	wigiiNcdEtp.options = wigiiNcdEtpOptions;ncdprivate('options');
 })(window, jQuery, wigiiNcd);