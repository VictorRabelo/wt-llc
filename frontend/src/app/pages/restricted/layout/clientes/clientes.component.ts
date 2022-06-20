import { Component } from '@angular/core';

import { ControllerBase } from '@app/controller/controller.base';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { ClienteService } from '@app/services/cliente.service';
import { MessageService } from 'primeng/api';

import { SubSink } from 'subsink';
import { ClienteFormComponent } from '@app/components/cliente-form/cliente-form.component';
import { Column } from '@app/models/Column';

@Component({
  selector: 'app-clientes',
  templateUrl: './clientes.component.html',
  styleUrls: ['./clientes.component.css'],
  providers: [ MessageService ]
})
export class ClientesComponent extends ControllerBase {
  
  private sub = new SubSink();

  loading: boolean = false;

  dados: any = [];
  columns: Column[];

  title: string = 'clientes';
  term: string;

  constructor(
    private clienteService: ClienteService, 
    private messageService: MessageService,
    private modalCtrl: NgbModal,
  ) {
    super();
  }

  ngOnInit() {
    this.columns = [
      { field: 'id_cliente', header: '#ID', id: 'id_cliente', sortIcon: true, crud: false, mask: 'none' },
      { field: 'name', header: 'Name', id: 'id_cliente', sortIcon: true, crud: false, mask: 'none' },
      { field: 'telefone', header: 'Telephone', id: 'id_cliente', sortIcon: true, crud: false, mask: 'phone' },
      { field: 'action', header: 'Action', id: 'id_cliente', sortIcon: false, crud: true, mask: 'none' },
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

  crudInTable(res: any){
    if(res.crud == 'delete'){
      this.delete(res.id)
    } else {
      this.openForm(res.crud, res.id)
    }
  }
  
  getAll(){
    this.loading = true;
    this.sub.sink = this.clienteService.getAll().subscribe(
      (res: any) => {
        this.loading = false;
        this.dados = res;
      },error => console.log(error))
  }

  delete(id){
    
    this.loading = true;

    this.clienteService.delete(id).subscribe(
      (res: any) => {
        this.getAll();
      },
      error => console.log(error),
      () => {
        this.messageService.add({key: 'bc', severity:'success', summary: 'Sucesso', detail: 'Excluido com Sucesso!'});
        this.loading = false;
      }
    );
  }

  ngOnDestroy(){
    this.sub.unsubscribe();
  }

}
