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
 * This language can be naturally used within the etp-vip-template.html for your own creations.
 * Created by Wigii.org (camille@wigii.org), 11.02.2017
 * Modified by Wigii.org (camille@wigii.org and lionel@wigii.org), 10.05.2017
 */ 
(function (window, $, wigiiNcd){ 

	/**
	 * Language holder
	 */
	var wigiiNcdEtp = {publishLanguage:true};
	wigiiNcdEtp.instantiationTime = Date.now();
	wigiiNcdEtp.ctxKey = 'WigiiNcdEtp_'+wigiiNcdEtp.instantiationTime;
	wigiiNcdEtp.version = function() {return "1.1";};
	
	// Execution environment

	/**
	 * Main HTML Emitter
	 */
	var html = wigiiNcd().getHtmlEmitter("#programOutput");
	wigiiNcdEtp.html = html;
	
	/**
	 * List of available symbols in French and English
	 */
	wigiiNcdEtp.createLanguageHolder = function() {
		return {};
	};
	var language = wigiiNcdEtp.createLanguageHolder();
	wigiiNcdEtp.language = language;
	
	
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
	wigiiNcdEtp.debugger = {};
	wigiiNcdEtp.debugger.initialize = function() {
		if($("#programWatcherContainer").length==0) {
			$("#programOutput").css('float','left').css('width','71%').after('<div id="programWatcherContainer"><div id="programWatcher"></div></div><div style="clear:both;"></div>');
		}
		$("#programWatcherContainer").show();
		return wigiiNcdEtp.debugger;
	};
	wigiiNcdEtp.debugger.$ = function() {return $("#programWatcherContainer");};
	
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
		html.impl.bouton = function(label, onClick, cssClass){html.putButton(label, onClick, cssClass); return html;};
		html.impl.input = function(cssClass){return html.createTextArea(cssClass);};
		html.impl.display = function(backgroundC,textC,cssClass) {return html.createTextInput(cssClass).color(backgroundC,textC);};
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
					gridCell.cellule = function(ligne,colonne) {return html.impl.grille.cellule(ligne,colonne);}
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
					wigiiNcdEtp.debugger.initialize().$().find("#programWatcher").html("<u>"+self.index+"</u> "+"Taille: "+self.taille()+"<br/>"+JSON.stringify(self.list));
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
				self.context = {currentFieldName:undefined};
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
					
					// Creates display according to type. Defaults to TextInput
					if(type) {
						switch(type) {
							case "TextArea":
								 self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createTextArea().color(couleurFond, couleurTexte);
								 break;
							case "PasswordInput":
								 self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createPasswordInput().color(couleurFond, couleurTexte);
								 break;
							default: throw wigiiNcd().createServiceException("Le type de champ '"+type+"' n'est pas supporté.",wigiiNcd().errorCodes.INVALID_ARGUMENT);
						}
					}
					else self.context.display = wigiiNcd().getHtmlEmitter('#'+self.context.id+' div.etp-value').createTextInput().color(couleurFond, couleurTexte);
					self.context.display.id = function() {return self.context.display.ctxKey;};
					self.context.display.$ = function() {return $('#'+self.context.display.ctxKey);};
					self.context.display.couleur = self.context.display.color;
					
					// Behavior: 
					// - when typing, saves text
					// - on click, changes current form field to this one
					self.context.display.onInput(function(d,txt){d.context.text=txt;self.context.changed = true;});
					self.context.display.$().click(function(){self.context.formulaire.context.currentFieldName=self.context.nom;});
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
				self.supprimer = function() {formulaire.delete(self.index);};

				// English translation
				self.createField = self.creerChamp;
				self.field = self.champ;
				self.fieldExist = self.champExiste;
				self.currentField = self.champCourant;
				self.empty = self.vider;
				self.delete = self.supprimer;
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
		delete: function(index) {
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
		champ : function(nom) {return formulaire.no('default').champ(nom);},
		champExiste : function(nom) {return formulaire.no('default').champExiste(nom);},
		champCourant : function(nom) {return formulaire.no('default').champCourant(nom);},
		vider : function() {return formulaire.no('default').vider();},
		supprimer : function() {return formulaire.no('default').supprimer();}
	};
	
	// Publish language
	wigiiNcdEtp.programme = programme; language.programme = wigiiNcdEtp.createLanguageHolder();
	wigiiNcdEtp.out = html.out; language.out = html.language;
	wigiiNcdEtp.h1 = html.h1; language.h1 = html.language;
	wigiiNcdEtp.p = html.p; language.p = html.language;
	wigiiNcdEtp.$p = html.$p; language.$p = html.language;
	wigiiNcdEtp.b = html.b; language.b = html.language;
	wigiiNcdEtp.i = html.i; language.i = html.language;
	wigiiNcdEtp.u = html.u; language.u = html.language;
	wigiiNcdEtp.a = html.a; language.a = html.language;
	wigiiNcdEtp.color = html.color; language.color = html.language;
	wigiiNcdEtp.$color = html.$color; language.$color = html.language;
	wigiiNcdEtp.bouton = html.bouton; language.bouton = html.language;
	wigiiNcdEtp.input = html.input; language.input = wigiiNcdEtp.createLanguageHolder();
	wigiiNcdEtp.display = html.display; language.display = wigiiNcdEtp.createLanguageHolder();
	
	wigiiNcdEtp.grille = html.grille; language.grille = wigiiNcdEtp.createLanguageHolder();
	/*
	language.fr.push('grille.texte');
	language.fr.push('grille.onclick');
	*/
	
	wigiiNcdEtp.panier = panier; 
	/*
	language.fr.push('panier.lire'); 
	language.fr.push('panier.lirePremier');
	language.fr.push('panier.lireDernier'); 
	language.fr.push('panier.remplacer');
	language.fr.push('panier.ajouter'); 
	language.fr.push('panier.sortirDernier');
	language.fr.push('panier.taille');
	language.fr.push('panier.montrer'); 
	language.fr.push('panier.exporter');
	language.fr.push('panier.importer'); 
	language.fr.push('panier.vider');
	*/
	wigiiNcdEtp.formulaire = formulaire;
	/*
	language.fr.push('formulaire.creerChamp'); 
	language.fr.push('formulaire.champ');
	language.fr.push('formulaire.champ.valeur');
	language.fr.push('formulaire.champ.label');
	language.fr.push('formulaire.champ.label.couleur');
	language.fr.push('formulaire.champ.couleur');
	language.fr.push('formulaire.champ.id');
	language.fr.push('formulaire.champ.nom');
	language.fr.push('formulaire.champ.$');
	language.fr.push('formulaire.champ.input');
	language.fr.push('formulaire.champ.vider');
	language.fr.push('formulaire.champ.focus');
	language.fr.push('formulaire.champCourant'); 
	language.fr.push('formulaire.vider');
	language.fr.push('formulaire.supprimer'); 
	*/
	wigiiNcdEtp.serveur = serveur; 
	/*
	language.fr.push('serveur.stockerDonnee'); language.fr.push('serveur.obtenirDonnee');
	*/
	
	// English translation
	wigiiNcdEtp.program = wigiiNcdEtp.programme; 
	/*
	language.en.push('program');
	language.en.push('out');
	language.en.push('h1');
	language.en.push('p');
	language.en.push('$p');
	language.en.push('b');
	language.en.push('i');
	language.en.push('u');
	language.en.push('a');
	language.en.push('color');
	language.en.push('$color');
	*/
	wigiiNcdEtp.button = wigiiNcdEtp.bouton; 
	/*
	language.en.push('button');
	language.en.push('input');
	language.en.push('display');
	*/
	wigiiNcdEtp.grid = wigiiNcdEtp.grille; 
	/*
	language.en.push('grid');
	*/
	/*
	wigiiNcdEtp.basket = wigiiNcdEtp.panier;
	wigiiNcdEtp.basket.read = wigiiNcdEtp.panier.lire; language.en.push('basket.read'); 
	wigiiNcdEtp.basket.readFirst = wigiiNcdEtp.panier.lirePremier; language.en.push('basket.readFirst');
	wigiiNcdEtp.basket.readLast =wigiiNcdEtp.panier.lireDernier; language.en.push('basket.readLast'); 
	wigiiNcdEtp.basket.replace = wigiiNcdEtp.panier.remplacer; language.en.push('basket.replace');
	wigiiNcdEtp.basket.add = wigiiNcdEtp.panier.ajouter; language.en.push('basket.add'); 
	wigiiNcdEtp.basket.removeLast = wigiiNcdEtp.panier.sortirDernier; language.en.push('basket.removeLast');
	wigiiNcdEtp.basket.size = wigiiNcdEtp.panier.taille; language.en.push('basket.size'); 
	wigiiNcdEtp.basket.show = wigiiNcdEtp.panier.montrer; language.en.push('basket.show');
	wigiiNcdEtp.basket.export = wigiiNcdEtp.panier.exporter; language.en.push('basket.export'); 
	wigiiNcdEtp.basket.import = wigiiNcdEtp.panier.importer; language.en.push('basket.import');
	wigiiNcdEtp.basket.empty = wigiiNcdEtp.panier.vider; language.en.push('basket.empty'); 	
	
	wigiiNcdEtp.form = wigiiNcdEtp.formulaire;
	wigiiNcdEtp.form.createField = wigiiNcdEtp.formulaire.creerChamp; language.en.push('formulaire.creerChamp'); 
	wigiiNcdEtp.form.field = wigiiNcdEtp.formulaire.champ; language.en.push('formulaire.champ');
	language.en.push('form.field.value');
	language.en.push('form.field.label');
	language.en.push('form.field.label.color');
	language.en.push('form.field.color');
	language.en.push('form.field.id');
	language.en.push('form.field.name');
	language.en.push('form.field.$');
	language.en.push('form.field.input');
	language.en.push('form.field.empty');
	language.en.push('form.field.focus');
	wigiiNcdEtp.form.currentField = wigiiNcdEtp.formulaire.champCourant; language.en.push('formulaire.champCourant'); 
	wigiiNcdEtp.form.empty = wigiiNcdEtp.formulaire.vider; language.en.push('formulaire.vider');
	//wigiiNcdEtp.form.delete = wigiiNcdEtp.formulaire.supprimer; language.en.push('formulaire.supprimer'); 
	
	wigiiNcdEtp.server = wigiiNcdEtp.serveur;
	wigiiNcdEtp.server.getData = wigiiNcdEtp.serveur.obtenirDonnee; language.en.push('server.getData');
	wigiiNcdEtp.server.storeData = wigiiNcdEtp.serveur.stockerDonnee; language.en.push('server.storeData');
	*/
	
	// Publish language to Window
	wigiiNcdEtp.doPublishLanguage = function(window) {
		if(wigiiNcdEtp.publishLanguage!==false) {
			// French symbols
			window.programme = wigiiNcdEtp.programme;
			window.out = wigiiNcdEtp.out;	
			window.h1 = wigiiNcdEtp.h1;
			window.p = wigiiNcdEtp.p;
			window.$p = wigiiNcdEtp.$p;
			window.b = wigiiNcdEtp.b;
			window.i = wigiiNcdEtp.i;
			window.u = wigiiNcdEtp.u;
			window.a = wigiiNcdEtp.a;
			window.color = wigiiNcdEtp.color;
			window.$color = wigiiNcdEtp.$color;
			window.bouton = wigiiNcdEtp.bouton;
			window.input = wigiiNcdEtp.input;
			window.display = wigiiNcdEtp.display;
			window.grille = wigiiNcdEtp.grille;
			window.panier = wigiiNcdEtp.panier;
			window.formulaire = wigiiNcdEtp.formulaire;
			window.serveur = wigiiNcdEtp.serveur;
			/* Deprecated symbols since 1.1 */
			window.couleur = wigiiNcdEtp.grille.couleur;
			window.texte = wigiiNcdEtp.grille.texte;
			//window.onclick = wigiiNcdEtp.grille.onclick;
			// English symbols
			window.program = wigiiNcdEtp.program;
			window.button = wigiiNcdEtp.button;
			window.grid = wigiiNcdEtp.grid;
			window.server = wigiiNcdEtp.server;
		}
	};
	
	// Bootstrap
	if(!window.wigiiNcdEtp || !window.wigiiNcdEtp.version || window.wigiiNcdEtp.version() < wigiiNcdEtp.version()) {
		// Publish symbols to window if flag is not explicitely set to false
		if(!window.wigiiNcdEtp || window.wigiiNcdEtp && window.wigiiNcdEtp.publishLanguage!==false) wigiiNcdEtp.doPublishLanguage(window);
		else wigiiNcdEtp.publishLanguage = false;
		window.wigiiNcdEtp = wigiiNcdEtp;
	}
	$("#footer").append('<span><i>&nbsp;(etp v.'+wigiiNcdEtp.version()+' loaded)</i></span>');
 })(window, jQuery, wigiiNcd);