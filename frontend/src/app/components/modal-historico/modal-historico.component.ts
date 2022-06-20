import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { SubSink } from 'subsink';
import { MessageService } from '@app/services/message.service';
import { HistoricoService } from '@app/services/historico.service';
import { _getShadowRoot } from '@angular/material/progress-spinner/typings/progress-spinner';
import { enterAnimationIcon } from '@app/animations';

@Component({
  selector: 'app-modal-historico',
  templateUrl: './modal-historico.component.html',
  styleUrls: ['./modal-historico.component.css'],
  animations: [ enterAnimationIcon ]
})
export class ModalHistoricoComponent implements OnInit, OnDestroy {

  private sub = new SubSink();

  loading: boolean = false;

  @Input() id: any;

  dados: any = {};
  dataSource: any[] = [];
  title: string;

  constructor(
    private activeModal: NgbActiveModal,
    private service: HistoricoService,
    private message: MessageService,
  ) {}

  ngOnInit() {
    if(this.id) {
      this.getShow(this.id);
    }
  }

  getShow(id){
    this.loading = true;

    this.dados.id = id;

    this.service.getById(id).subscribe(res => {
      this.dataSource = res;
    }, error => {
      console.log(error)
      this.message.toastError(error.message);
      this.loading = false;
    }, () => {
      this.loading = false;
    });
  }

  close(params = undefined) {
    this.activeModal.close(params);
  }
  
  submit(form: NgForm) {
    if (!form.valid) {
      return false;
    }
    
    this.loading = true;

    this.create(this.dados);
    form.reset();
  }

  create(dados) {
    this.service.store(dados).subscribe(res => {
      this.getShow(this.id);
    }, error => {
      console.log(error)
      this.message.toastError(error.message);
      this.loading = false;
    }, () => {
      this.message.toastSuccess('');
      this.loading = false;
    });
  }
  
  deleteComentario(id){
    this.loading = true;
    
    this.service.delete(id).subscribe(res => {
      this.getShow(this.id);
    }, error => {
      console.log(error)
      this.message.toastError(error.message);
      this.loading = false;
    }, () => {
      this.loading = false;
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }
}
