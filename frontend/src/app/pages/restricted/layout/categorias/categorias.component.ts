import { Component } from '@angular/core';

import { ControllerBase } from '@app/controller/controller.base';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { MessageService } from 'primeng/api';

import { SubSink } from 'subsink';
import { ClienteFormComponent } from '@app/components/cliente-form/cliente-form.component';
import { CategoriaService } from '@app/services/categoria.service';
import { Column } from '@app/models/Column';

@Component({
  selector: 'app-categorias',
  templateUrl: './categorias.component.html',
  styleUrls: ['./categorias.component.css'],
  providers: [ MessageService ]
})
export class CategoriasComponent extends ControllerBase {
  
  private sub = new SubSink();

  loading: boolean = false;
  loadingDelete: boolean = false;

  dados: any = [];
  columns: Column[];

  title: string = 'categorias';
  term: string;

  constructor(
    private service: CategoriaService, 
    private messageService: MessageService,
    private modalCtrl: NgbModal,
  ) {
    super();
  }

  ngOnInit() {
    this.columns = [
      { field: 'id_categoria', header: '#ID', id: 'id_categoria', sortIcon: true, crud: false, mask: 'none' },
      { field: 'categoria', header: 'Categorie', id: 'id_categoria', sortIcon: true, crud: false, mask: 'none' },
      { field: 'subcategoria', header: 'Subcategorie', id: 'id_categoria', sortIcon: true, crud: false, mask: 'none' },
      { field: 'action', header: 'Action', id: 'id_categoria', sortIcon: false, crud: true, mask: 'none' },
    ];
    this.getAll();
  }

  openForm(crud, item = undefined){
    const modalRef = this.modalCtrl.open(ClienteFormComponent, { size: 'sm', backdrop: 'static' });
    modalRef.componentInstance.data = item;
    modalRef.componentInstance.crud = crud;
    modalRef.componentInstance.module = this.title;
    modalRef.result.then(res => {
      if(res.message){
        this.messageService.add({key: 'bc', severity:'success', summary: 'Sucesso', detail: res.message});
      }
      this.getAll();
    })
  }

  getAll(){
    this.loading = true;
    this.sub.sink = this.service.getAll().subscribe(
      (res: any) => {
        this.loading = false;
        this.dados = res;
      },error => console.log(error))
  }

  crudInTable(res: any){
    if(res.crud == 'delete'){
      this.delete(res.id)
    } else {
      this.openForm(res.crud, res.id)
    }
  }
  
  delete(id){
    
    this.loadingDelete = true;

    this.service.delete(id).subscribe(
      (res: any) => {
        this.getAll();
      },
      error => console.log(error),
      () => {
        this.messageService.add({key: 'bc', severity:'success', summary: 'Sucesso', detail: 'Excluido com Sucesso!'});
        this.loadingDelete = false;
      }
    );
  }

  ngOnDestroy(){
    this.sub.unsubscribe();
  }

}