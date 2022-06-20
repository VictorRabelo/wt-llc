import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FilterFormComponent } from '@app/components/filter-form/filter-form.component';
import { ContabilidadeService } from '@app/services/contabilidade.service';
import { MessageService } from '@app/services/message.service';
import { VendaService } from '@app/services/venda.service';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { NgxSpinnerService } from 'ngx-spinner';
import { SubSink } from 'subsink';

@Component({
  selector: 'app-contabilidades',
  templateUrl: './contabilidades.component.html',
  styleUrls: ['./contabilidades.component.css']
})
export class ContabilidadesComponent implements OnInit, OnDestroy {
  private sub = new SubSink();
  public today: number = Date.now();

  dataSource: any[] = [];

  loading: boolean = false;

  filters: any = { date: '' };

  totalContabilidade: number = 0;
  totalMensal: number = 0;
  recebido: number = 0;

  term: string;

  constructor(
    private router: Router,
    private modalCtrl: NgbModal,
    private service: ContabilidadeService,
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
    this.totalContabilidade = 0;
    this.totalMensal = 0;
    this.recebido = 0;
  }

  getAll() {
    this.zerar();

    this.loading = true;
    this.sub.sink = this.service.getAll(this.filters).subscribe(res => {
      this.dataSource = res.vendas;
      this.totalContabilidade = res.totalContabilidade;
      this.totalMensal = res.totalMensal;
      this.recebido = res.pago;
      this.today = res.data;

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
      title: 'Start new account?',
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

      this.router.navigate([`/restricted/contabilidades/${res.id}`]);
    }, error =>{
      this.loading = false;
      this.message.toastError(error.message)
      console.log(error)
    })
  }

  editVenda(id) {
    this.router.navigate([`/restricted/contabilidades/${id}`]);
  }

  deleteConfirm(id) {
    this.message.swal.fire({
      title: 'Attention!',
      icon: 'warning',
      html: `Do you want to delete this account ? `,
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
