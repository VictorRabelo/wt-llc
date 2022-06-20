import { Component } from '@angular/core';

import { ControllerBase } from '@app/controller/controller.base';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';

import { EstoqueService } from '@app/services/estoque.service';
import { DashboardService } from '@app/services/dashboard.service';
import { EstoqueFormComponent } from '@app/components/estoque-form/estoque-form.component';

import { MessageService } from 'primeng/api';

import { SubSink } from 'subsink';

import 'bootstrap';
import { NgxIzitoastService } from 'ngx-izitoast';
import { Column, Status } from '@app/models/Column';
declare let $: any;

@Component({
  selector: 'app-estoque',
  templateUrl: './estoque.component.html',
  styleUrls: ['./estoque.component.css'],
  providers: [ MessageService ]
})
export class EstoqueComponent extends ControllerBase {
  
  private sub = new SubSink();

  loading: Boolean = false;
  progress: Boolean = false;

  dados: any[] = [];
  columns: Column[];
  status: Status[] = [
    { field: 'In Stock', value: 'ok',       color: 'success'},
    { field: 'Sent',     value: 'pendente', color: 'warning'},
    { field: 'Sold',     value: 'vendido',  color: 'danger'},
    { field: 'Paid',     value: 'pago',     color: 'primary'}
  ];

  queryParams: any = { status: 'all'};
  term: string;

  enviados: number = 0;
  pagos: number = 0;
  estoque: number = 0;
  vendidos: number = 0;
  totalEstoque: number = 0;

  constructor(
    private estoqueService: EstoqueService,
    private modalCtrl: NgbModal,
    private dashboardService: DashboardService,
    private iziToast: NgxIzitoastService,
    ) {
    super();
  }

  ngOnInit() {
  
    this.columns = [
      { field: 'id_produto', header: '#COD.',           id: 'id_produto', sortIcon: true,  crud: false, mask: 'none' },
      { field: 'path',       header: 'Photo',           id: 'id_produto', sortIcon: true,  crud: false, mask: 'none' },
      { field: 'und',        header: 'Und.',            id: 'id_produto', sortIcon: true,  crud: false, mask: 'none' },
      { field: 'name',       header: 'Name',            id: 'id_produto', sortIcon: true,  crud: false, mask: 'title-case' },
      { field: 'unitario',   header: 'Cost',            id: 'id_produto', sortIcon: true,  crud: false, mask: 'brl' },
      { field: 'preco',      header: 'Suggested price', id: 'id_produto', sortIcon: true,  crud: false, mask: 'brl' },
      { field: 'categoria',  header: 'Category',        id: 'id_produto', sortIcon: true,  crud: false, mask: 'title-case' },
      { field: 'fornecedor', header: 'Provider',        id: 'id_produto', sortIcon: true,  crud: false, mask: 'title-case' },
      { field: 'status',     header: 'Status',          id: 'id_produto', sortIcon: true,  crud: false, mask: 'status', roleStatus: this.status },
      { field: 'action',     header: 'Action',          id: 'id_produto', sortIcon: false, crud: true,  mask: 'none' },
    ];
    this.getStart();
  }

  getStart(){
    this.loading = true;
    this.getAll(this.queryParams);
    this.getProdutosEstoque();
    this.getProdutosEnviados();
    this.getProdutosPagos();
    this.getProdutosVendidos();
    this.getProdutosCadastrados();
  }

  crudInTable(res: any){
    if(res.crud == 'delete'){
      this.delete(res.id)
    } else {
      this.openForm(res.crud, res.id)
    }
  }

  openForm(crud, item = undefined){
    const modalRef = this.modalCtrl.open(EstoqueFormComponent, { size: 'lg', backdrop: 'static' });
    modalRef.componentInstance.data = item;
    modalRef.componentInstance.crud = crud;
    modalRef.result.then(res => {
      if(res.message){
        this.iziToast.success({
          title: 'Sucesso!',
          message: res.message,
          position: 'topRight'
        });
      }
      this.getStart();
    })
  }

  getAll(queryParams: any = undefined){
    
    this.sub.sink = this.estoqueService.getAll(queryParams).subscribe(
      (res: any) => {
        this.dados = res;
      },
      error => {
        this.loading = false;
        console.log(error)
      },
      () => {
        this.loading = false;
      })
  }
  
  rangeStatus() {
    this.loading = true;

    this.sub.sink = this.estoqueService.getAll(this.queryParams).subscribe(
      (res: any) => {
        this.dados = res;
      },
      error => {
        this.loading = false;
        console.log(error)
      },
      () => {
        this.loading = false;
      })
  }

  getProdutosEnviados(){

    this.sub.sink = this.dashboardService.getProdutosEnviados().subscribe((res: any) => {
      this.loading = false;
      this.enviados = res;
    },
    error => {
      console.log(error)
      this.loading = false;
    });
  }
  
  getProdutosCadastrados(){
    
    this.sub.sink = this.dashboardService.getProdutosCadastrados().subscribe((res: any) => {
      this.loading = false;
      this.totalEstoque = res;
    },
    error => {
      console.log(error)
      this.loading = false;
    });
  }

  getProdutosPagos(){

    this.sub.sink = this.dashboardService.getProdutosPagos().subscribe((res: any) => {
      this.loading = false;
      this.pagos = res;
    },
    error => {
      console.log(error)
      this.loading = false;
    });
  }
  
  getProdutosEstoque(){

    this.sub.sink = this.dashboardService.getProdutosEstoque().subscribe((res: any) => {
      this.loading = false;
      this.estoque = res;
    },
    error => {
      console.log(error)
      this.loading = false;
    });
  }
  
  getProdutosVendidos(){

    this.sub.sink = this.dashboardService.getProdutosVendidos().subscribe((res: any) => {
      this.loading = false;
      this.vendidos = res;
    },
    error => {
      console.log(error)
      this.loading = false;
    });
  }

  delete(id){
    this.loading = true;

    this.sub.sink = this.estoqueService.delete(id).subscribe(
      (res: any) => {
        this.loading = false;
        this.iziToast.success({
          title: 'Sucesso!',
          message: "Deletado com sucesso!",
          position: 'topRight'
        });
        this.getStart();
      },
      error => {
        this.iziToast.error({
          title: 'Atenção!',
          message: error,
          position: 'topRight'
        });
        console.log(error)
        this.loading = false;
      }
    );
  }

  ngOnDestroy(){
    this.sub.unsubscribe();
  }

}
