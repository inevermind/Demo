import { NgModule }      	from '@angular/core';
import { BrowserModule } 	from '@angular/platform-browser';
import { MaterialModule }   from '@angular/material';
import { FlexLayoutModule } from "@angular/flex-layout";

import 'hammerjs';

import { AppComponent }  	from './app.component';

import { ContactModule }  	from './contact/contact.module';


@NgModule({
    imports: [
    	BrowserModule,
    	MaterialModule.forRoot(),
        FlexLayoutModule.forRoot(),
        ContactModule
    ],
    declarations: [ AppComponent ],
    bootstrap:    [ AppComponent ]
})
export class AppModule { }
