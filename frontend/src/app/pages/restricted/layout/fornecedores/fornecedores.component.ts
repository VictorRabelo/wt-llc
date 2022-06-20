import { Component } from '@angular/core';

import { ControllerBase } from '@app/controller/controller.base';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { FornecedorService } from '@app/services/fornecedor.service';
import { MessageService } from 'primeng/api';

import { SubSink } from 'subsink';
import { ClienteFormComponent } from '@app/components/cliente-form/cliente-form.component';
import { Column } from '@app/models/Column';

@Component({
  selector: 'app-fornecedores',
  templateUrl: './fornecedores.component.html',
  styleUrls: ['./fornecedores.component.css'],
  providers: [ MessageService ]
})
export class FornecedoresComponent extends ControllerBase {
  
  private sub = new SubSink();

  loading: boolean = false;
  loadingDelete: boolean = false;

  dados: any = [];
  columns: Column[];

  title: string = 'fornecedores';
  term: string;

  constructor(
    private fornecedorService: FornecedorService, 
    private messageService: MessageService,
    private modalCtrl: NgbModal,
  ) {
    super();
  }

  ngOnInit() {
    this.columns = [
      { field: 'id_fornecedor', header: '#ID', id: 'id_fornecedor', sortIcon: true, crud: false, mask: 'none' },
      { field: 'fornecedor', header: 'Name', id: 'id_fornecedor', sortIcon: true, crud: false, mask: 'none' },
      { field: 'telefone', header: 'Telephone', id: 'id_fornecedor', sortIcon: true, crud: false, mask: 'phone' },
      { field: 'action', header: 'Action', id: 'id_fornecedor', sortIcon: false, crud: true, mask: 'none' },
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
    this.sub.sink = this.fornecedorService.getAll().subscribe(
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

    this.fornecedorService.delete(id).subscribe(
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