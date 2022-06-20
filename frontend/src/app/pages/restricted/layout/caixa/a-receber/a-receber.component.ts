import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { ControllerBase } from '@app/controller/controller.base';
import { VendaService } from '@app/services/venda.service';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { MessageService } from '@app/services/message.service';

import { NgxSpinnerService } from 'ngx-spinner';
import { SubSink } from 'subsink';

declare let $: any;
import 'bootstrap';
import { ModalDebitarComponent } from '@app/components/modal-debitar/modal-debitar.component';
import { RelatorioService } from '@app/services/relatorio.service';

@Component({
  selector: 'app-a-receber',
  templateUrl: './a-receber.component.html',
  styleUrls: ['./a-receber.component.css']
})
export class AReceberComponent extends ControllerBase {

  private sub = new SubSink();

  dataSource: any[] = [];

  loading: boolean = false;

  filters: any = { aReceber: true };

  saldoReceber: number = 0;
  saldoPago: number = 0;
  totalRestante: number = 0;
  
  term: string;

  constructor(
    private modalCtrl: NgbModal,
    private router: Router,
    private service: VendaService,
    private serviceRelatorio: RelatorioService,
    private message: MessageService,
    private spinner: NgxSpinnerService,
  ) { 
    super();
  }

  ngOnInit(): void {
    this.getStart();
  }

  getStart(){
    this.loading = true;
    this.getAll();
  }

  getAll() {
    this.sub.sink = this.service.getAll(this.filters).subscribe(res => {
      this.dataSource = res.dadosReceber;
      this.saldoReceber = res.saldoReceber;
      this.saldoPago = res.saldoPago;
      this.totalRestante = res.totalRestante;

    },error =>{
      
      this.loading = false;
      this.message.toastError(error.message);
      console.log(error);

    },()=> {
      this.loading = false;
    });
  }

  add() {
    this.message.swal.fire({
      title: 'Start new sale?',
      icon: 'question',
      confirmButtonText: 'Confirme',
      cancelButtonText: 'Back',
      showCancelButton: true
    }).then(res => {
      if (res.isConfirmed) {
        this.createVenda();
      }
    })
  }

  createVenda() {
    this.loading = true;
    this.service.store({}).subscribe(res => {
      this.router.navigate([`/restricted/vendas/${res.id_venda}`]);
    }, error =>{
      this.loading = false;
      this.message.toastError(error.message)
      console.log(error)
    })
  }

  openConta(item) {
    item.cliente = item.nameCliente;

    const modalRef = this.modalCtrl.open(ModalDebitarComponent, { size: 'sm', backdrop: 'static' });
    modalRef.componentInstance.data = item;
    modalRef.result.then(res => {
      this.getAll();
    })
  }

  deleteConfirm(item) {
    this.message.swal.fire({
      title: 'Attention!',
      icon: 'warning',
      html: `Do you want to delete this sale ?`,
      confirmButtonText: 'Confirme',
      cancelButtonText: 'Back',
      showCancelButton: true
    }).then(res => {
      if (res.isConfirmed) {
        this.delete(item);
      }
    })
  }

  delete(id){
    this.loading = true;
    this.spinner.show();

    this.service.delete(id).subscribe(res => {
      if (res.message) {
        this.message.toastSuccess(res.message)
      }
      this.getAll();
    },error =>{
      this.loading = false;
      this.message.toastError(error.message)
      console.log(error)
    }, () => {
      this.spinner.hide();
    });
  }
  
  downloadDetalhe(id){
    this.loading = true;
    this.sub.sink = this.serviceRelatorio.getVendaAReceber(id).subscribe(
      (res: any) => {
        this.downloadPDF(res.file, res.data, 'detalhes-areceber')
      },
      error => console.log(error),
      ()=>{
        this.loading = false;
      })
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

}
