import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FilterFormComponent } from '@app/components/filter-form/filter-form.component';
import { MessageService } from '@app/services/message.service';
import { VendaService } from '@app/services/venda.service';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { NgxSpinnerService } from 'ngx-spinner';
import { SubSink } from 'subsink';

@Component({
  selector: 'app-sales',
  templateUrl: './sales.component.html',
  styleUrls: ['./sales.component.css']
})
export class SalesComponent implements OnInit, OnDestroy {
  private sub = new SubSink();
  public today: number = Date.now();

  dataSource: any[] = [];

  loading: boolean = false;

  filters: any = { date: '' };

  totalVendas: number = 0;
  totalMensal: number = 0;
  recebido: number = 0;
  lucro: number = 0;
  mediaTotalLittle: number = 0.00;
  qtdTotalLittle: number = 0;
  
  term: string;

  constructor(
    private router: Router,
    private modalCtrl: NgbModal,
    private service: VendaService,
    private message: MessageService,
    private spinner: NgxSpinnerService,
  ) { }

  ngOnInit(): void {
    this.getStart();
  }

  getStart(){
    this.getAll();
  }
  
  filterDate() {
    const modalRef = this.modalCtrl.open(FilterFormComponent, { size: 'sm', backdrop: 'static' });
    modalRef.result.then(res => {
      if(res.date){
        this.filters.date = res.date;
        this.getAll();
      }
    })
  }
  
  zerar() {
    this.totalVendas = 0;
    this.totalMensal = 0;
    this.recebido = 0;
    this.lucro = 0;
    this.mediaTotalLittle = 0.00;
    this.qtdTotalLittle = 0;
  }

  getAll() {
    this.zerar();

    this.loading = true;
    this.sub.sink = this.service.getAll(this.filters).subscribe(res => {
      this.dataSource = res.vendas;
      this.totalVendas = res.totalVendas;
      this.totalMensal = res.totalMensal;
      this.recebido = res.pago;
      this.lucro = res.lucro;
      this.today = res.data;
      this.qtdTotalLittle = res.mediaLittle.qtdVendaTotal?res.mediaLittle.qtdVendaTotal:0;
      this.mediaTotalLittle = res.mediaLittle.mediaTotal?res.mediaLittle.mediaTotal:0.00;

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
      
      if(res.message) {
        this.message.toastError(res.message);
        this.loading = false;
        return false;
      };

      this.router.navigate([`/restricted/vendas/${res.id_venda}`]);
    }, error =>{
      this.loading = false;
      this.message.toastError(error.message)
      console.log(error)
    })
  }

  editVenda(id) {
    this.router.navigate([`/restricted/vendas/${id}`]);
  }

  deleteConfirm(id) {
    this.message.swal.fire({
      title: 'Attention!',
      icon: 'warning',
      input: 'checkbox',
      inputValue: 0,
      inputPlaceholder: 'Return products to stock ?',
      html: `Do you want to delete this sale ? `,
      confirmButtonText: 'Confirme',
      cancelButtonText: 'Back',
      showCancelButton: true
    }).then(res => {
      if (res.isConfirmed) {
        this.delete(id, { extornarProduto: res.value });
      }
    })
  }

  delete(id, queryParams?: any) {
    this.loading = true;
    this.spinner.show();

    this.service.delete(id, queryParams).subscribe(res => {
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
  
  ngOnDestroy() {
    this.sub.unsubscribe();
  }

}
