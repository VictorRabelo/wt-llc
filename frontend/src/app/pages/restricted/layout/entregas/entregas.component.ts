import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FilterFormComponent } from '@app/components/filter-form/filter-form.component';
import { EntregaService } from '@app/services/entrega.service';
import { MessageService } from '@app/services/message.service';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { NgxSpinnerService } from 'ngx-spinner';
import { SubSink } from 'subsink';

@Component({
  selector: 'app-entregas',
  templateUrl: './entregas.component.html',
  styleUrls: ['./entregas.component.css']
})
export class EntregasComponent implements OnInit, OnDestroy {
  private sub = new SubSink();
  public today: number = Date.now();

  dataSource: any[] = [];

  loading: boolean = false;

  filters: any = { date: '' };

  totalVendas: number = 0;
  
  term: string;

  constructor(
    private router: Router,
    private service: EntregaService,
    private modalCtrl: NgbModal,
    private message: MessageService,
    private spinner: NgxSpinnerService,
  ) { }

  ngOnInit(): void {
    this.getStart();
  }

  getStart(): void{
    this.loading = true;
    this.getAll();
  }

  filterDate() {
    const modalRef = this.modalCtrl.open(FilterFormComponent, { size: 'sm', backdrop: 'static' });
    modalRef.result.then(res => {
      if(res.date){
        this.filters.date = res.date;
  
        this.loading = true;
        this.getAll();
      }
    })
  }

  getAll() {
    this.sub.sink = this.service.getAll(this.filters).subscribe(res => {
      this.dataSource = res.entregas;
      this.totalVendas = res.totalVendas;
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
      title: 'Start new delivery?',
      icon: 'question',
      confirmButtonText: 'Confirme',
      cancelButtonText: 'Back',
      showCancelButton: true
    }).then(res => {
      if (res.isConfirmed) {
        this.createEntrega();
      }
    })
  }

  createEntrega() {
    this.loading = true;
    this.service.store({}).subscribe(res => {
      this.router.navigate([`/restricted/entregas/${res.id_entrega}`]);
    }, error =>{
      this.loading = false;
      this.message.toastError(error.message)
      console.log(error)
    })
  }

  editVenda(id) {
    this.router.navigate([`/restricted/entregas/${id}`]);
  }

  deleteConfirm(item) {
    this.message.swal.fire({
      title: 'Attention!',
      icon: 'warning',
      html: `Do you want to delete this delivery?`,
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
  
  ngOnDestroy() {
    this.sub.unsubscribe();
  }

}
