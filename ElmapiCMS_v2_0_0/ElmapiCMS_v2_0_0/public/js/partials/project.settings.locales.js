"use strict";(self.webpackChunk=self.webpackChunk||[]).push([[518],{1007:(a,n,i)=>{i.d(n,{Z:()=>e});const e={props:{type:{type:String,default:"submit"},color:{type:String,default:"gray-800"},hover:{type:String,default:null},padding:{type:String,default:null},disabled:{type:Boolean,default:!1}},computed:{bgColor:function(){var a="";if(this.disabled)a="bg-indigo-200 cursor-not-allowed";else{var n="bg-"+this.color,i=this.color.split("-"),e=null==this.hover?"bg-"+i[0]+"-"+(parseInt(i[1])+100):"bg-"+this.hover,r="bg-"+i[0]+"-"+(parseInt(i[1])+100);i[0],parseInt(i[1]),i[0];a=n+" hover:"+e+" active:"+r}return null===this.padding?a+=" p-3 ":a+=" "+this.padding+" ",a}}}},9475:(a,n,i)=>{i.d(n,{s:()=>e,x:()=>r});var e=function(){var a=this;return(0,a._self._c)("button",{staticClass:"items-center border border-transparent rounded-sm text-sm text-white focus:outline-none transition ease-in-out duration-150",class:a.bgColor,attrs:{type:a.type,disabled:a.disabled}},[a._t("default")],2)},r=[]},4487:(a,n,i)=>{i.d(n,{Z:()=>s});var e=i(9475),r=i(1718);const s=(0,i(1900).Z)(r.Z,e.s,e.x,!1,null,null,null).exports},3343:(a,n,i)=>{i.d(n,{Z:()=>r});const e={props:["project"],methods:{checkRole:i(3787).Z}};const r=(0,i(1900).Z)(e,(function(){var a=this,n=a._self._c;return n("div",{staticClass:"p-4 flex border-b"},[n("div",{staticClass:"w-full"},[n("div",{staticClass:"text-xl font-bold"},[a._v("\n            "+a._s(a.project.name)+"\n        ")]),a._v(" "),n("div",{staticClass:"text-sm"},[a._v(a._s(a.project.description))])]),a._v(" "),n("div",{staticClass:"flex items-center"},[void 0!==a.project.id&&a.checkRole(["admin"+a.project.id])?n("router-link",{staticClass:"text-xl text-gray-600 cursor-pointer",attrs:{to:{name:"projects.settings",params:{project_id:a.project.id}},"exact-active-class":"bg-none"}},[n("i",{staticClass:"fas fa-cog"})]):a._e()],1)])}),[],!1,null,null,null).exports},9684:(a,n,i)=>{i.r(n),i.d(n,{default:()=>o});var e=i(3343),r=i(4215),s=i(4487),t=i(3616);const l={components:{ProjectHeader:e.Z,SettingsNav:r.Z,UiButton:s.Z},data:function(){return{project:{},locales:[],addLocaleData:null}},methods:{getProject:function(){var a=this;axios.get("/admin/projects/settings/locales/"+this.$route.params.project_id).then((function(n){a.project=n.data,null!==a.project.locales&&(a.project.locales=n.data.locales.split(","))}))},getLocale:function(a){return t[a]},addLocale:function(){var a=this;axios.post("/admin/projects/settings/locales/add/"+this.$route.params.project_id,{locale:this.addLocaleData}).then((function(n){a.$toast.success("Locale added to the project."),a.getProject(),a.addLocaleData=null}),(function(n){422==n.response.status&&a.$toast.error("Locale has already added.")}))},setDefaultLocale:function(a){var n=this;this.$swal.fire({title:"Are you sure",text:"you want to change the default locale for this project?"}).then((function(i){i.value&&axios.post("/admin/projects/settings/locales/change-default-locale/"+n.project.id,{locale:a}).then((function(a){n.$toast.success("Default locale has been changed."),n.getProject()}))}))},deleteLocale:function(a){var n=this;this.$swal.fire({title:"Are you sure",text:"you want to delete this locale?"}).then((function(i){i.value&&axios.post("/admin/projects/settings/locales/delete-locale/"+n.project.id,{locale:a}).then((function(a){n.$toast.success("Locale deleted."),n.getProject()}),(function(a){422==a.response.status&&n.$toast.error("Default locale can not be deleted.")}))}))}},mounted:function(){var a=this;this.getProject(),Object.entries(t).forEach((function(n,i){a.locales.push({id:n[0],name:n[1]})}))}};const o=(0,i(1900).Z)(l,(function(){var a=this,n=a._self._c;return n("div",{staticClass:"flex h-full"},[n("div",{staticClass:"w-96 h-full bg-white overflow-x-hidden"},[n("project-header",{staticClass:"bg-white",attrs:{project:a.project}}),a._v(" "),n("settings-nav",{attrs:{project:a.project}})],1),a._v(" "),n("div",{staticClass:"w-full overflow-x-hidden"},[n("div",{staticClass:"p-4"},[n("h4",{staticClass:"mb-2 p-2 font-bold text-xl"},[a._v("Localization")]),a._v(" "),n("div",{staticClass:"bg-white mt-2 rounded-md p-4 w-full xl:w-3/5"},[n("div",[a._m(0),a._v(" "),n("div",{staticClass:"overflow-x-auto mt-1 flex rounded-sm"},[n("table",{staticClass:"min-w-full divide-y divide-gray-200 border"},[n("tbody",{staticClass:"bg-white divide-y divide-gray-200"},a._l(a.project.locales,(function(i){return n("tr",{key:i},[n("td",{staticClass:"px-6 py-3 text-sm align-top"},[a._v("\n                                        "+a._s(i)+"\n                                    ")]),a._v(" "),n("td",{staticClass:"px-6 py-3 text-sm align-top"},[a._v("\n                                        "+a._s(a.getLocale(i))+"\n                                    ")]),a._v(" "),n("td",{staticClass:"px-6 py-3 text-sm w-px text-center font-bold whitespace-nowrap"},[i==a.project.default_locale?n("div",[a._v("Default")]):n("div",{staticClass:"ml-2 cursor-pointer text-indigo-500 py-1 px-3 rounded-sm hover:bg-gray-100",on:{click:function(n){return a.setDefaultLocale(i)}}},[a._v("Set as default")])]),a._v(" "),n("td",{staticClass:"px-6 py-3 text-sm w-px text-center"},[i!=a.project.default_locale?n("div",{staticClass:"ml-2 cursor-pointer text-red-500 py-1 px-3 rounded-sm hover:bg-gray-100",on:{click:function(n){return a.deleteLocale(i)}}},[n("i",{staticClass:"fa fa-trash-alt"})]):a._e()])])})),0)])])]),a._v(" "),n("div",{staticClass:"mt-10"},[a._m(1),a._v(" "),n("div",{staticClass:"flex"},[n("div",{staticClass:"w-1/2 mr-2"},[n("v-select",{staticClass:"v-select",attrs:{options:a.locales,"get-option-key":function(a){return a.id},"get-option-label":function(a){return a.id+" - "+a.name},reduce:function(a){return a.id},clearable:!1,value:function(a){return a[0]},placeholder:"Select Locale"},model:{value:a.addLocaleData,callback:function(n){a.addLocaleData=n},expression:"addLocaleData"}})],1),a._v(" "),n("ui-button",{attrs:{color:"indigo-500"},nativeOn:{click:function(n){return a.addLocale.apply(null,arguments)}}},[a._v("+ Add")])],1)])])])])])}),[function(){var a=this._self._c;return a("div",{staticClass:"w-full flex justify-between"},[a("div",{staticClass:"text-lg font-bold"},[this._v("Available Locales")])])},function(){var a=this._self._c;return a("div",{staticClass:"w-full flex justify-between"},[a("div",{staticClass:"text-lg font-bold"},[this._v("Add a Locale")])])}],!1,null,null,null).exports},4215:(a,n,i)=>{i.d(n,{Z:()=>r});const e={props:["project"],methods:{checkRole:i(3787).Z}};const r=(0,i(1900).Z)(e,(function(){var a=this,n=a._self._c;return n("div",{staticClass:"p-4 bg-white"},[n("h4",{staticClass:"mb-2 p-2 font-bold text-lg"},[a._v("Settings")]),a._v(" "),n("ul",[n("li",{staticClass:"mb-2"},[void 0!==a.project.id?n("router-link",{staticClass:"block w-full p-2 cursor-pointer hover:bg-gray-100 rounded",attrs:{to:{name:"projects.settings",params:{project_id:a.project.id}}}},[a._v("Project")]):a._e()],1),a._v(" "),n("li",{staticClass:"mb-2"},[void 0!==a.project.id&&a.checkRole(["super_admin"])?n("router-link",{staticClass:"block w-full p-2 cursor-pointer hover:bg-gray-100 rounded",attrs:{to:{name:"projects.settings.locales",params:{project_id:a.project.id}}}},[a._v("Localization")]):a._e()],1),a._v(" "),n("li",{staticClass:"mb-2"},[void 0!==a.project.id&&a.checkRole(["super_admin"])?n("router-link",{staticClass:"block w-full p-2 cursor-pointer hover:bg-gray-100 rounded",attrs:{to:{name:"projects.settings.users",params:{project_id:a.project.id}}}},[a._v("Users & Roles")]):a._e()],1),a._v(" "),n("li",{staticClass:"mb-2"},[void 0!==a.project.id&&a.checkRole(["super_admin"])?n("router-link",{staticClass:"block w-full p-2 cursor-pointer hover:bg-gray-100 rounded",attrs:{to:{name:"projects.settings.api",params:{project_id:a.project.id}}}},[a._v("API Access")]):a._e()],1),a._v(" "),n("li",{staticClass:"mb-2"},[void 0!==a.project.id&&a.checkRole(["super_admin"])?n("router-link",{staticClass:"block w-full p-2 cursor-pointer hover:bg-gray-100 rounded",attrs:{to:{name:"projects.settings.webhooks",params:{project_id:a.project.id}}}},[a._v("Webhooks")]):a._e()],1)])])}),[],!1,null,null,null).exports},1718:(a,n,i)=>{i.d(n,{Z:()=>e});const e=i(1007).Z},3616:a=>{a.exports=JSON.parse('{"af":"Afrikaans","af_NA":"Afrikaans (Namibia)","af_ZA":"Afrikaans (South Africa)","ak":"Akan","ak_GH":"Akan (Ghana)","sq":"Albanian","sq_AL":"Albanian (Albania)","sq_XK":"Albanian (Kosovo)","sq_MK":"Albanian (Macedonia)","am":"Amharic","am_ET":"Amharic (Ethiopia)","ar":"Arabic","ar_DZ":"Arabic (Algeria)","ar_BH":"Arabic (Bahrain)","ar_TD":"Arabic (Chad)","ar_KM":"Arabic (Comoros)","ar_DJ":"Arabic (Djibouti)","ar_EG":"Arabic (Egypt)","ar_ER":"Arabic (Eritrea)","ar_IQ":"Arabic (Iraq)","ar_IL":"Arabic (Israel)","ar_JO":"Arabic (Jordan)","ar_KW":"Arabic (Kuwait)","ar_LB":"Arabic (Lebanon)","ar_LY":"Arabic (Libya)","ar_MR":"Arabic (Mauritania)","ar_MA":"Arabic (Morocco)","ar_OM":"Arabic (Oman)","ar_PS":"Arabic (Palestinian Territories)","ar_QA":"Arabic (Qatar)","ar_SA":"Arabic (Saudi Arabia)","ar_SO":"Arabic (Somalia)","ar_SS":"Arabic (South Sudan)","ar_SD":"Arabic (Sudan)","ar_SY":"Arabic (Syria)","ar_TN":"Arabic (Tunisia)","ar_AE":"Arabic (United Arab Emirates)","ar_EH":"Arabic (Western Sahara)","ar_YE":"Arabic (Yemen)","hy":"Armenian","hy_AM":"Armenian (Armenia)","as":"Assamese","as_IN":"Assamese (India)","az":"Azerbaijani","az_AZ":"Azerbaijani (Azerbaijan)","az_Cyrl_AZ":"Azerbaijani (Cyrillic, Azerbaijan)","az_Cyrl":"Azerbaijani (Cyrillic)","az_Latn_AZ":"Azerbaijani (Latin, Azerbaijan)","az_Latn":"Azerbaijani (Latin)","bm":"Bambara","bm_Latn_ML":"Bambara (Latin, Mali)","bm_Latn":"Bambara (Latin)","eu":"Basque","eu_ES":"Basque (Spain)","be":"Belarusian","be_BY":"Belarusian (Belarus)","bn":"Bengali","bn_BD":"Bengali (Bangladesh)","bn_IN":"Bengali (India)","bs":"Bosnian","bs_BA":"Bosnian (Bosnia & Herzegovina)","bs_Cyrl_BA":"Bosnian (Cyrillic, Bosnia & Herzegovina)","bs_Cyrl":"Bosnian (Cyrillic)","bs_Latn_BA":"Bosnian (Latin, Bosnia & Herzegovina)","bs_Latn":"Bosnian (Latin)","br":"Breton","br_FR":"Breton (France)","bg":"Bulgarian","bg_BG":"Bulgarian (Bulgaria)","my":"Burmese","my_MM":"Burmese (Myanmar (Burma))","ca":"Catalan","ca_AD":"Catalan (Andorra)","ca_FR":"Catalan (France)","ca_IT":"Catalan (Italy)","ca_ES":"Catalan (Spain)","zh":"Chinese","zh_CN":"Chinese (China)","zh_HK":"Chinese (Hong Kong SAR China)","zh_MO":"Chinese (Macau SAR China)","zh_Hans_CN":"Chinese (Simplified, China)","zh_Hans_HK":"Chinese (Simplified, Hong Kong SAR China)","zh_Hans_MO":"Chinese (Simplified, Macau SAR China)","zh_Hans_SG":"Chinese (Simplified, Singapore)","zh_Hans":"Chinese (Simplified)","zh_SG":"Chinese (Singapore)","zh_TW":"Chinese (Taiwan)","zh_Hant_HK":"Chinese (Traditional, Hong Kong SAR China)","zh_Hant_MO":"Chinese (Traditional, Macau SAR China)","zh_Hant_TW":"Chinese (Traditional, Taiwan)","zh_Hant":"Chinese (Traditional)","kw":"Cornish","kw_GB":"Cornish (United Kingdom)","hr":"Croatian","hr_BA":"Croatian (Bosnia & Herzegovina)","hr_HR":"Croatian (Croatia)","cs":"Czech","cs_CZ":"Czech (Czech Republic)","da":"Danish","da_DK":"Danish (Denmark)","da_GL":"Danish (Greenland)","nl":"Dutch","nl_AW":"Dutch (Aruba)","nl_BE":"Dutch (Belgium)","nl_BQ":"Dutch (Caribbean Netherlands)","nl_CW":"Dutch (Curaçao)","nl_NL":"Dutch (Netherlands)","nl_SX":"Dutch (Sint Maarten)","nl_SR":"Dutch (Suriname)","dz":"Dzongkha","dz_BT":"Dzongkha (Bhutan)","en":"English","en_AS":"English (American Samoa)","en_AI":"English (Anguilla)","en_AG":"English (Antigua & Barbuda)","en_AU":"English (Australia)","en_BS":"English (Bahamas)","en_BB":"English (Barbados)","en_BE":"English (Belgium)","en_BZ":"English (Belize)","en_BM":"English (Bermuda)","en_BW":"English (Botswana)","en_IO":"English (British Indian Ocean Territory)","en_VG":"English (British Virgin Islands)","en_CM":"English (Cameroon)","en_CA":"English (Canada)","en_KY":"English (Cayman Islands)","en_CX":"English (Christmas Island)","en_CC":"English (Cocos (Keeling) Islands)","en_CK":"English (Cook Islands)","en_DG":"English (Diego Garcia)","en_DM":"English (Dominica)","en_ER":"English (Eritrea)","en_FK":"English (Falkland Islands)","en_FJ":"English (Fiji)","en_GM":"English (Gambia)","en_GH":"English (Ghana)","en_GI":"English (Gibraltar)","en_GD":"English (Grenada)","en_GU":"English (Guam)","en_GG":"English (Guernsey)","en_GY":"English (Guyana)","en_HK":"English (Hong Kong SAR China)","en_IN":"English (India)","en_IE":"English (Ireland)","en_IM":"English (Isle of Man)","en_JM":"English (Jamaica)","en_JE":"English (Jersey)","en_KE":"English (Kenya)","en_KI":"English (Kiribati)","en_LS":"English (Lesotho)","en_LR":"English (Liberia)","en_MO":"English (Macau SAR China)","en_MG":"English (Madagascar)","en_MW":"English (Malawi)","en_MY":"English (Malaysia)","en_MT":"English (Malta)","en_MH":"English (Marshall Islands)","en_MU":"English (Mauritius)","en_FM":"English (Micronesia)","en_MS":"English (Montserrat)","en_NA":"English (Namibia)","en_NR":"English (Nauru)","en_NZ":"English (New Zealand)","en_NG":"English (Nigeria)","en_NU":"English (Niue)","en_NF":"English (Norfolk Island)","en_MP":"English (Northern Mariana Islands)","en_PK":"English (Pakistan)","en_PW":"English (Palau)","en_PG":"English (Papua New Guinea)","en_PH":"English (Philippines)","en_PN":"English (Pitcairn Islands)","en_PR":"English (Puerto Rico)","en_RW":"English (Rwanda)","en_WS":"English (Samoa)","en_SC":"English (Seychelles)","en_SL":"English (Sierra Leone)","en_SG":"English (Singapore)","en_SX":"English (Sint Maarten)","en_SB":"English (Solomon Islands)","en_ZA":"English (South Africa)","en_SS":"English (South Sudan)","en_SH":"English (St. Helena)","en_KN":"English (St. Kitts & Nevis)","en_LC":"English (St. Lucia)","en_VC":"English (St. Vincent & Grenadines)","en_SD":"English (Sudan)","en_SZ":"English (Swaziland)","en_TZ":"English (Tanzania)","en_TK":"English (Tokelau)","en_TO":"English (Tonga)","en_TT":"English (Trinidad & Tobago)","en_TC":"English (Turks & Caicos Islands)","en_TV":"English (Tuvalu)","en_UM":"English (U.S. Outlying Islands)","en_VI":"English (U.S. Virgin Islands)","en_UG":"English (Uganda)","en_GB":"English (United Kingdom)","en_US":"English (United States)","en_VU":"English (Vanuatu)","en_ZM":"English (Zambia)","en_ZW":"English (Zimbabwe)","eo":"Esperanto","et":"Estonian","et_EE":"Estonian (Estonia)","ee":"Ewe","ee_GH":"Ewe (Ghana)","ee_TG":"Ewe (Togo)","fo":"Faroese","fo_FO":"Faroese (Faroe Islands)","fi":"Finnish","fi_FI":"Finnish (Finland)","fr":"French","fr_DZ":"French (Algeria)","fr_BE":"French (Belgium)","fr_BJ":"French (Benin)","fr_BF":"French (Burkina Faso)","fr_BI":"French (Burundi)","fr_CM":"French (Cameroon)","fr_CA":"French (Canada)","fr_CF":"French (Central African Republic)","fr_TD":"French (Chad)","fr_KM":"French (Comoros)","fr_CG":"French (Congo - Brazzaville)","fr_CD":"French (Congo - Kinshasa)","fr_CI":"French (Côte d’Ivoire)","fr_DJ":"French (Djibouti)","fr_GQ":"French (Equatorial Guinea)","fr_FR":"French (France)","fr_GF":"French (French Guiana)","fr_PF":"French (French Polynesia)","fr_GA":"French (Gabon)","fr_GP":"French (Guadeloupe)","fr_GN":"French (Guinea)","fr_HT":"French (Haiti)","fr_LU":"French (Luxembourg)","fr_MG":"French (Madagascar)","fr_ML":"French (Mali)","fr_MQ":"French (Martinique)","fr_MR":"French (Mauritania)","fr_MU":"French (Mauritius)","fr_YT":"French (Mayotte)","fr_MC":"French (Monaco)","fr_MA":"French (Morocco)","fr_NC":"French (New Caledonia)","fr_NE":"French (Niger)","fr_RE":"French (Réunion)","fr_RW":"French (Rwanda)","fr_SN":"French (Senegal)","fr_SC":"French (Seychelles)","fr_BL":"French (St. Barthélemy)","fr_MF":"French (St. Martin)","fr_PM":"French (St. Pierre & Miquelon)","fr_CH":"French (Switzerland)","fr_SY":"French (Syria)","fr_TG":"French (Togo)","fr_TN":"French (Tunisia)","fr_VU":"French (Vanuatu)","fr_WF":"French (Wallis & Futuna)","ff":"Fulah","ff_CM":"Fulah (Cameroon)","ff_GN":"Fulah (Guinea)","ff_MR":"Fulah (Mauritania)","ff_SN":"Fulah (Senegal)","gl":"Galician","gl_ES":"Galician (Spain)","lg":"Ganda","lg_UG":"Ganda (Uganda)","ka":"Georgian","ka_GE":"Georgian (Georgia)","de":"German","de_AT":"German (Austria)","de_BE":"German (Belgium)","de_DE":"German (Germany)","de_LI":"German (Liechtenstein)","de_LU":"German (Luxembourg)","de_CH":"German (Switzerland)","el":"Greek","el_CY":"Greek (Cyprus)","el_GR":"Greek (Greece)","gu":"Gujarati","gu_IN":"Gujarati (India)","ha":"Hausa","ha_GH":"Hausa (Ghana)","ha_Latn_GH":"Hausa (Latin, Ghana)","ha_Latn_NE":"Hausa (Latin, Niger)","ha_Latn_NG":"Hausa (Latin, Nigeria)","ha_Latn":"Hausa (Latin)","ha_NE":"Hausa (Niger)","ha_NG":"Hausa (Nigeria)","he":"Hebrew","he_IL":"Hebrew (Israel)","hi":"Hindi","hi_IN":"Hindi (India)","hu":"Hungarian","hu_HU":"Hungarian (Hungary)","is":"Icelandic","is_IS":"Icelandic (Iceland)","ig":"Igbo","ig_NG":"Igbo (Nigeria)","id":"Indonesian","id_ID":"Indonesian (Indonesia)","ga":"Irish","ga_IE":"Irish (Ireland)","it":"Italian","it_IT":"Italian (Italy)","it_SM":"Italian (San Marino)","it_CH":"Italian (Switzerland)","ja":"Japanese","ja_JP":"Japanese (Japan)","kl":"Kalaallisut","kl_GL":"Kalaallisut (Greenland)","kn":"Kannada","kn_IN":"Kannada (India)","ks":"Kashmiri","ks_Arab_IN":"Kashmiri (Arabic, India)","ks_Arab":"Kashmiri (Arabic)","ks_IN":"Kashmiri (India)","kk":"Kazakh","kk_Cyrl_KZ":"Kazakh (Cyrillic, Kazakhstan)","kk_Cyrl":"Kazakh (Cyrillic)","kk_KZ":"Kazakh (Kazakhstan)","km":"Khmer","km_KH":"Khmer (Cambodia)","ki":"Kikuyu","ki_KE":"Kikuyu (Kenya)","rw":"Kinyarwanda","rw_RW":"Kinyarwanda (Rwanda)","ko":"Korean","ko_KP":"Korean (North Korea)","ko_KR":"Korean (South Korea)","ky":"Kyrgyz","ky_Cyrl_KG":"Kyrgyz (Cyrillic, Kyrgyzstan)","ky_Cyrl":"Kyrgyz (Cyrillic)","ky_KG":"Kyrgyz (Kyrgyzstan)","lo":"Lao","lo_LA":"Lao (Laos)","lv":"Latvian","lv_LV":"Latvian (Latvia)","ln":"Lingala","ln_AO":"Lingala (Angola)","ln_CF":"Lingala (Central African Republic)","ln_CG":"Lingala (Congo - Brazzaville)","ln_CD":"Lingala (Congo - Kinshasa)","lt":"Lithuanian","lt_LT":"Lithuanian (Lithuania)","lu":"Luba-Katanga","lu_CD":"Luba-Katanga (Congo - Kinshasa)","lb":"Luxembourgish","lb_LU":"Luxembourgish (Luxembourg)","mk":"Macedonian","mk_MK":"Macedonian (Macedonia)","mg":"Malagasy","mg_MG":"Malagasy (Madagascar)","ms":"Malay","ms_BN":"Malay (Brunei)","ms_Latn_BN":"Malay (Latin, Brunei)","ms_Latn_MY":"Malay (Latin, Malaysia)","ms_Latn_SG":"Malay (Latin, Singapore)","ms_Latn":"Malay (Latin)","ms_MY":"Malay (Malaysia)","ms_SG":"Malay (Singapore)","ml":"Malayalam","ml_IN":"Malayalam (India)","mt":"Maltese","mt_MT":"Maltese (Malta)","gv":"Manx","gv_IM":"Manx (Isle of Man)","mr":"Marathi","mr_IN":"Marathi (India)","mn":"Mongolian","mn_Cyrl_MN":"Mongolian (Cyrillic, Mongolia)","mn_Cyrl":"Mongolian (Cyrillic)","mn_MN":"Mongolian (Mongolia)","ne":"Nepali","ne_IN":"Nepali (India)","ne_NP":"Nepali (Nepal)","nd":"North Ndebele","nd_ZW":"North Ndebele (Zimbabwe)","se":"Northern Sami","se_FI":"Northern Sami (Finland)","se_NO":"Northern Sami (Norway)","se_SE":"Northern Sami (Sweden)","no":"Norwegian","no_NO":"Norwegian (Norway)","nb":"Norwegian Bokmål","nb_NO":"Norwegian Bokmål (Norway)","nb_SJ":"Norwegian Bokmål (Svalbard & Jan Mayen)","nn":"Norwegian Nynorsk","nn_NO":"Norwegian Nynorsk (Norway)","or":"Oriya","or_IN":"Oriya (India)","om":"Oromo","om_ET":"Oromo (Ethiopia)","om_KE":"Oromo (Kenya)","os":"Ossetic","os_GE":"Ossetic (Georgia)","os_RU":"Ossetic (Russia)","ps":"Pashto","ps_AF":"Pashto (Afghanistan)","fa":"Persian","fa_AF":"Persian (Afghanistan)","fa_IR":"Persian (Iran)","pl":"Polish","pl_PL":"Polish (Poland)","pt":"Portuguese","pt_AO":"Portuguese (Angola)","pt_BR":"Portuguese (Brazil)","pt_CV":"Portuguese (Cape Verde)","pt_GW":"Portuguese (Guinea-Bissau)","pt_MO":"Portuguese (Macau SAR China)","pt_MZ":"Portuguese (Mozambique)","pt_PT":"Portuguese (Portugal)","pt_ST":"Portuguese (São Tomé & Príncipe)","pt_TL":"Portuguese (Timor-Leste)","pa":"Punjabi","pa_Arab_PK":"Punjabi (Arabic, Pakistan)","pa_Arab":"Punjabi (Arabic)","pa_Guru_IN":"Punjabi (Gurmukhi, India)","pa_Guru":"Punjabi (Gurmukhi)","pa_IN":"Punjabi (India)","pa_PK":"Punjabi (Pakistan)","qu":"Quechua","qu_BO":"Quechua (Bolivia)","qu_EC":"Quechua (Ecuador)","qu_PE":"Quechua (Peru)","ro":"Romanian","ro_MD":"Romanian (Moldova)","ro_RO":"Romanian (Romania)","rm":"Romansh","rm_CH":"Romansh (Switzerland)","rn":"Rundi","rn_BI":"Rundi (Burundi)","ru":"Russian","ru_BY":"Russian (Belarus)","ru_KZ":"Russian (Kazakhstan)","ru_KG":"Russian (Kyrgyzstan)","ru_MD":"Russian (Moldova)","ru_RU":"Russian (Russia)","ru_UA":"Russian (Ukraine)","sg":"Sango","sg_CF":"Sango (Central African Republic)","gd":"Scottish Gaelic","gd_GB":"Scottish Gaelic (United Kingdom)","sr":"Serbian","sr_BA":"Serbian (Bosnia & Herzegovina)","sr_Cyrl_BA":"Serbian (Cyrillic, Bosnia & Herzegovina)","sr_Cyrl_XK":"Serbian (Cyrillic, Kosovo)","sr_Cyrl_ME":"Serbian (Cyrillic, Montenegro)","sr_Cyrl_RS":"Serbian (Cyrillic, Serbia)","sr_Cyrl":"Serbian (Cyrillic)","sr_XK":"Serbian (Kosovo)","sr_Latn_BA":"Serbian (Latin, Bosnia & Herzegovina)","sr_Latn_XK":"Serbian (Latin, Kosovo)","sr_Latn_ME":"Serbian (Latin, Montenegro)","sr_Latn_RS":"Serbian (Latin, Serbia)","sr_Latn":"Serbian (Latin)","sr_ME":"Serbian (Montenegro)","sr_RS":"Serbian (Serbia)","sh":"Serbo-Croatian","sh_BA":"Serbo-Croatian (Bosnia & Herzegovina)","sn":"Shona","sn_ZW":"Shona (Zimbabwe)","ii":"Sichuan Yi","ii_CN":"Sichuan Yi (China)","si":"Sinhala","si_LK":"Sinhala (Sri Lanka)","sk":"Slovak","sk_SK":"Slovak (Slovakia)","sl":"Slovenian","sl_SI":"Slovenian (Slovenia)","so":"Somali","so_DJ":"Somali (Djibouti)","so_ET":"Somali (Ethiopia)","so_KE":"Somali (Kenya)","so_SO":"Somali (Somalia)","es":"Spanish","es_AR":"Spanish (Argentina)","es_BO":"Spanish (Bolivia)","es_IC":"Spanish (Canary Islands)","es_EA":"Spanish (Ceuta & Melilla)","es_CL":"Spanish (Chile)","es_CO":"Spanish (Colombia)","es_CR":"Spanish (Costa Rica)","es_CU":"Spanish (Cuba)","es_DO":"Spanish (Dominican Republic)","es_EC":"Spanish (Ecuador)","es_SV":"Spanish (El Salvador)","es_GQ":"Spanish (Equatorial Guinea)","es_GT":"Spanish (Guatemala)","es_HN":"Spanish (Honduras)","es_MX":"Spanish (Mexico)","es_NI":"Spanish (Nicaragua)","es_PA":"Spanish (Panama)","es_PY":"Spanish (Paraguay)","es_PE":"Spanish (Peru)","es_PH":"Spanish (Philippines)","es_PR":"Spanish (Puerto Rico)","es_ES":"Spanish (Spain)","es_US":"Spanish (United States)","es_UY":"Spanish (Uruguay)","es_VE":"Spanish (Venezuela)","sw":"Swahili","sw_KE":"Swahili (Kenya)","sw_TZ":"Swahili (Tanzania)","sw_UG":"Swahili (Uganda)","sv":"Swedish","sv_AX":"Swedish (Åland Islands)","sv_FI":"Swedish (Finland)","sv_SE":"Swedish (Sweden)","tl":"Tagalog","tl_PH":"Tagalog (Philippines)","ta":"Tamil","ta_IN":"Tamil (India)","ta_MY":"Tamil (Malaysia)","ta_SG":"Tamil (Singapore)","ta_LK":"Tamil (Sri Lanka)","te":"Telugu","te_IN":"Telugu (India)","th":"Thai","th_TH":"Thai (Thailand)","bo":"Tibetan","bo_CN":"Tibetan (China)","bo_IN":"Tibetan (India)","ti":"Tigrinya","ti_ER":"Tigrinya (Eritrea)","ti_ET":"Tigrinya (Ethiopia)","to":"Tongan","to_TO":"Tongan (Tonga)","tr":"Turkish","tr_CY":"Turkish (Cyprus)","tr_TR":"Turkish (Turkey)","uk":"Ukrainian","uk_UA":"Ukrainian (Ukraine)","ur":"Urdu","ur_IN":"Urdu (India)","ur_PK":"Urdu (Pakistan)","ug":"Uyghur","ug_Arab_CN":"Uyghur (Arabic, China)","ug_Arab":"Uyghur (Arabic)","ug_CN":"Uyghur (China)","uz":"Uzbek","uz_AF":"Uzbek (Afghanistan)","uz_Arab_AF":"Uzbek (Arabic, Afghanistan)","uz_Arab":"Uzbek (Arabic)","uz_Cyrl_UZ":"Uzbek (Cyrillic, Uzbekistan)","uz_Cyrl":"Uzbek (Cyrillic)","uz_Latn_UZ":"Uzbek (Latin, Uzbekistan)","uz_Latn":"Uzbek (Latin)","uz_UZ":"Uzbek (Uzbekistan)","vi":"Vietnamese","vi_VN":"Vietnamese (Vietnam)","cy":"Welsh","cy_GB":"Welsh (United Kingdom)","fy":"Western Frisian","fy_NL":"Western Frisian (Netherlands)","yi":"Yiddish","yo":"Yoruba","yo_BJ":"Yoruba (Benin)","yo_NG":"Yoruba (Nigeria)","zu":"Zulu","zu_ZA":"Zulu (South Africa)"}')}}]);